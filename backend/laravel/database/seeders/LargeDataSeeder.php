<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * 性能検証用の投稿を、一定件数ごとのバルクINSERTで作成するSeeder。
 */
class LargeDataSeeder extends Seeder
{
    /** 件数を指定しなかった場合に投入する投稿数。 */
    public const DEFAULT_POST_COUNT = 300_000;

    /** 1回のINSERTで投入する投稿数。 */
    private const CHUNK_SIZE = 5_000;

    /**
     * 指定件数の投稿を5,000件ずつ生成し、postsへバルクINSERTする。
     *
     * 最初に投稿者を1人作成し、その後は投稿の生成とINSERTをチャンク単位で繰り返す。
     * 途中で失敗した場合は投稿者を削除し、外部キーのON DELETE CASCADEで投入済み投稿も削除する。
     * DatabaseSeederからは呼び出さないため、通常のdb-resetでは大量データを生成しない。
     *
     * 例: run(10_000)を呼ぶと、5,000件ずつ2回のINSERTで1万投稿を作成する。
     *
     * @throws RuntimeException 本番環境で実行した場合、または投稿件数が不正な場合
     */
    public function run(int $postCount = self::DEFAULT_POST_COUNT): void
    {
        // 本番データへの誤投入を防ぐため、投稿を作る前に実行環境を確認する。
        if (app()->environment('production')) {
            Log::warning('大量データSeederの本番実行を拒否しました。', [
                'environment' => app()->environment(),
                'post_count' => $postCount,
            ]);

            throw new RuntimeException('LargeDataSeederは本番環境では実行できません。');
        }

        // 後続のチャンク計算は投稿が1件以上あることを前提とするため、不正な件数を拒否する。
        if ($postCount < 1) {
            Log::warning('大量データSeederに不正な投稿件数が指定されました。', [
                'post_count' => $postCount,
            ]);

            throw new RuntimeException('投稿件数には1以上の整数を指定してください。');
        }

        $startedAt = microtime(true);
        $currentChunk = 0;
        $insertedPosts = 0;
        $stage = '投稿者作成';
        $userId = null;

        Log::info('大量ダミー投稿の投入を開始します。', [
            'post_count' => $postCount,
            'chunk_size' => self::CHUNK_SIZE,
        ]);

        try {
            // 外部キー制約を満たすため、すべてのダミー投稿で共有する投稿者を1人だけ作成する。
            $userId = $this->insertUser();
            $createdAt = now()->format('Y-m-d H:i:s');
            $stage = '投稿投入';

            // 全投稿を5,000件ずつ処理し、最後は残っている件数だけを対象にする。
            for ($offset = 0; $offset < $postCount; $offset += self::CHUNK_SIZE) {
                $currentChunk++;
                $rowsInChunk = min(self::CHUNK_SIZE, $postCount - $offset);
                $posts = $this->makePosts($offset, $rowsInChunk, $userId, $createdAt);

                // 複数行を1回のクエリで保存し、1投稿ずつINSERTする場合とのクエリ回数の差を作る。
                DB::table('posts')->insert($posts);
                $insertedPosts += count($posts);
            }
        } catch (Throwable $e) {
            $cleanupStatus = 'not_required';

            if ($userId !== null) {
                try {
                    // 専用ユーザーを削除し、ON DELETE CASCADEでこの実行中に投入した投稿もまとめて削除する。
                    $deletedUsers = DB::table('users')->where('id', $userId)->delete();
                    $cleanupStatus = $deletedUsers === 1 ? 'completed' : 'target_not_found';
                } catch (Throwable $cleanupException) {
                    $cleanupStatus = 'failed';

                    // 後片付けの失敗を元の投入エラーと区別し、残存データを手動で確認できるようにする。
                    Log::error('大量ダミー投稿の後片付けに失敗しました。', [
                        'user_id' => $userId,
                        'inserted_posts' => $insertedPosts,
                        'exception' => $cleanupException,
                    ]);
                }
            }

            // 失敗した処理位置と後片付け結果を記録し、再実行できる状態か判断できるようにする。
            Log::error('大量ダミー投稿の投入中にエラーが発生しました。', [
                'stage' => $stage,
                'chunk' => $currentChunk,
                'requested_post_count' => $postCount,
                'inserted_posts' => $insertedPosts,
                'cleanup_status' => $cleanupStatus,
                'exception' => $e,
            ]);

            throw $e;
        }

        Log::info('大量ダミー投稿の投入が完了しました。', [
            'inserted_posts' => $insertedPosts,
            'elapsed_seconds' => round(microtime(true) - $startedAt, 2),
        ]);
    }

    /**
     * 投稿者を1人作成する。
     *
     * メールアドレスに実行ごとのUUIDを含めるため、Seederを繰り返しても既存ユーザーと重複しない。
     * 例: ユーザーIDが10として採番された場合は10を返す。
     */
    private function insertUser(): int
    {
        return (int) DB::table('users')->insertGetId([
            'name' => '性能検証ユーザー',
            'email' => 'large-data-' . Str::uuid() . '@example.test',
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * 1回のバルクINSERTで保存する投稿データを生成する。
     *
     * 通常投稿に加え、10投稿ごとの「頻出語」と1,000投稿ごとの「希少語」を用意する。
     * 内容の自然さではなく、後続の全文検索でヒット件数による性能差を比較できることを優先する。
     *
     * 例: offsetが0、countが5,000なら、連番1〜5,000の投稿データを返す。
     *
     * @return list<array<string, int|string|null>>
     */
    private function makePosts(int $offset, int $count, int $userId, string $createdAt): array
    {
        $posts = [];

        for ($index = 0; $index < $count; $index++) {
            $serial = $offset + $index + 1;
            $searchTerm = match (true) {
                $serial % 1_000 === 0 => '希少語',
                $serial % 10 === 0 => '頻出語',
                default => '通常',
            };

            $posts[] = [
                'user_id' => $userId,
                'content' => "{$searchTerm} 投稿{$serial}",
                'image_mime' => null,
                'image_data' => null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];
        }

        return $posts;
    }
}
