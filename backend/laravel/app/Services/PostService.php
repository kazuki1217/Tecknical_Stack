<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * 投稿に関するビジネスロジックを担当するサービス
 */
class PostService
{
    /**
     * 投稿一覧をページ単位で取得する
     *
     * @param  int  $perPage  1ページあたりの取得件数
     * @return LengthAwarePaginator<Post> 投稿一覧
     */
    public function getAll(int $perPage = 20): LengthAwarePaginator
    {
        return Post::with(['user', 'tags', 'comments.user']) // ユーザー・タグ・コメント情報を含める
            ->orderByDesc('created_at') // 作成日が新しい順番に並び替え
            ->orderByDesc('id') // 同時刻投稿でもページをまたいだ順序が安定するようにする
            ->paginate($perPage); // Laravelがリクエストのpageクエリを自動で読み、指定ページの一定件数だけ取得する
    }

    /**
     * 投稿を作成する
     *
     * @param  User  $user  投稿者
     * @param  array<string, mixed>  $validated  バリデーション済み入力
     * @return Post 作成された投稿
     *
     * @throws RuntimeException 保存に失敗した場合（投稿・タグともに保存されない）
     */
    public function create(User $user, array $validated): Post
    {
        // 画像データとMIMEタイプを初期化
        $imageData = null;
        $imageMime = null;

        // 画像ファイルが存在する場合
        if (array_key_exists('image', $validated) && $validated['image']) {
            // 送信された画像ファイルを取得し、バイナリ化
            $imageData = file_get_contents($validated['image']->getRealPath());
            // 画像のMIMEタイプ（例: image/jpeg, image/pngなど）を取得
            $imageMime = $validated['image']->getMimeType();
        }

        try {
            // トランザクションを使用する（途中で失敗して「タグが付いていない投稿」だけが残らないようにするため）
            $post = DB::transaction(function () use ($user, $validated, $imageData, $imageMime) {
                // フォームに投稿した情報を DB に保存
                $post = Post::create([
                    'user_id' => $user->id,
                    'content' => $validated['content'] ?? null,
                    'image_data' => $imageData,
                    'image_mime' => $imageMime,
                ]);

                // タグの紐付け
                $post->tags()->sync($this->resolveTagIds($validated['tags'] ?? null));

                return $post;
            });
        } catch (\Throwable $e) {
            // 扱っていたデータを例外メッセージに詰め直す（ロールバックで投稿がDBに残らず、呼び出し元のログからのみ追跡できるため）
            throw new RuntimeException(sprintf(
                '投稿とタグの登録に失敗しました。実行したユーザーID: %d, タグ: %s, 発生箇所: %s:%d, エラー内容: %s',
                $user->id,
                $validated['tags'] ?? 'なし',
                $e->getFile(),
                $e->getLine(),
                $e->getMessage(),
            ), previous: $e);
        }

        return $post->load(['user', 'tags']);
    }

    /**
     * 投稿を削除する
     *
     * @param  Post  $post  対象の投稿
     * @return Post 削除された投稿（ユーザー情報込み）
     */
    public function delete(Post $post): Post
    {
        $post->load(['user', 'tags', 'comments.user']); // 関連情報を含める
        $post->delete(); // 投稿データを削除

        return $post;
    }

    /**
     * 投稿を更新する
     *
     * @param  Post  $post  対象の投稿
     * @param  array<string, string>  $validated  バリデーション済み入力
     * @return Post 更新された投稿（ユーザー情報込み）
     *
     * @throws RuntimeException 保存に失敗した場合（本文・タグともに更新されない）
     */
    public function update(Post $post, array $validated): Post
    {
        try {
            // トランザクションを使用する（途中で失敗して「本文だけ更新されてタグが消えた投稿」が残らないようにするため）
            DB::transaction(function () use ($post, $validated) {
                // 投稿データを更新
                $post->content = $validated['content'];
                $post->save();

                if (array_key_exists('tags', $validated)) {
                    $post->tags()->sync($this->resolveTagIds($validated['tags']));
                }
            });
        } catch (\Throwable $e) {
            // 扱っていたデータを例外メッセージに詰め直す（ロールバック後もモデルには更新後の値が残り、DBの状態と区別が付かないため）
            throw new RuntimeException(sprintf(
                '本文とタグの更新に失敗しました。投稿ID: %d, タグ: %s, 発生箇所: %s:%d, エラー内容: %s',
                $post->id,
                $validated['tags'] ?? 'なし',
                $e->getFile(),
                $e->getLine(),
                $e->getMessage(),
            ), previous: $e);
        }

        return $post->load(['user', 'tags', 'comments.user']);
    }

    /**
     * 投稿を検索する
     *
     * @param  string  $content  検索キーワードやハッシュタグを含む文字列（例: "海 クラゲ #夜"）
     * @param  int  $perPage  1ページあたりの取得件数
     * @return LengthAwarePaginator<Post> 検索結果
     */
    public function search(string $content, int $perPage = 20): LengthAwarePaginator
    {
        // 空白（全角・半角・連続）を半角スペースに統一し、前後の空白を削除
        $normalizedKeyword = trim((string) preg_replace('/(?:\x{3000}|[[:space:]])+/u', ' ', $content));
        if ($normalizedKeyword === '') {
            // 空文字では投稿を取得せず、通常の検索結果と同じページ情報を返す。
            return Post::query()->whereRaw('1 = 0')->paginate($perPage);
        }

        // 半角スペースで単語を分割し、重複を除外
        $terms = array_values(array_unique(array_filter(explode(' ', $normalizedKeyword), fn ($token) => $token !== '')));

        $hashtagTerms = []; // ハッシュタグを格納する配列
        $keywordTerms = []; // 検索キーワードを格納する配列
        foreach ($terms as $term) {
            // 先頭が # の場合はハッシュタグとして扱い、その他は検索キーワードとして扱う
            if (str_starts_with($term, '#')) {
                $tagName = ltrim($term, '#');
                if ($tagName !== '') {
                    $hashtagTerms[] = $tagName;
                }

                continue;
            }
            $keywordTerms[] = $term;
        }

        // クエリビルダを使用して、キーワードとタグの両方の条件を満たす投稿を検索
        $query = Post::with(['user', 'tags', 'comments.user'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($keywordTerms !== []) {
            // 各キーワードを必須のフレーズにして、ngramで日本語を検索しつつ複数語のAND条件を維持する。

            // 例: ['データベース', '勉強'] を +"データベース" +"勉強" に変換する。
            $booleanQuery = implode(' ', array_map(
                static fn (string $term): string => '+"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $term).'"',
                $keywordTerms
            ));

            // BOOLEAN MODEを使用し、ngramトークンの部分一致ではなく、検索語全体が本文内で一致する投稿を検索する。
            // 例: 「データベース」が保存されている状態で「データベースあ」と検索した場合、「スあ」トークンが含まれないため一致しない。
            $query->whereRaw(
                'MATCH(content) AGAINST (? IN BOOLEAN MODE)',
                [$booleanQuery]
            );
        }

        // ハッシュタグが複数ある場合は全てを含む投稿を対象とする（AND条件）
        foreach ($hashtagTerms as $hashtagTerm) {
            $query->whereHas('tags', function ($tagQuery) use ($hashtagTerm) {
                $tagQuery->where('name', 'LIKE', "%{$hashtagTerm}%");
            });
        }

        // 大量ヒット時もレスポンスとメモリ使用量が肥大化しないよう、指定件数だけ取得する。
        return $query->paginate($perPage);
    }

    /**
     * コメントを作成する
     *
     * @param  Post  $post  対象の投稿
     * @param  User  $user  コメント投稿者
     * @param  string  $content  コメント本文
     * @return Comment 作成されたコメント
     */
    public function createComment(Post $post, User $user, string $content): Comment
    {
        $comment = $post->comments()->create([
            'user_id' => $user->id,
            'content' => $content,
        ]);

        return $comment->load('user');
    }

    /**
     * コメントを削除する
     *
     * @param  Comment  $comment  対象コメント
     * @return Comment 削除されたコメント
     */
    public function deleteComment(Comment $comment): Comment
    {
        $comment->load('user');
        $comment->delete();

        return $comment;
    }

    /**
     * カンマ区切りのタグ文字列を tag_id 配列に変換する
     *
     * @param  string|null  $rawTags  例: "Laravel,React,API"
     * @return array<int, int> tag_id の配列
     */
    private function resolveTagIds(?string $rawTags): array
    {
        if (! $rawTags) {
            return [];
        }

        $tagNames = collect(explode(',', $rawTags))
            ->map(fn ($name) => trim($name))
            ->filter() // 空文字を除外
            ->unique() // 重複を除外
            ->take(10) // 最大10件までに制限
            ->values(); // キーを 0,1,2,... に振り直す

        if ($tagNames->isEmpty()) {
            return [];
        }

        // 既存タグをまとめて取得する（タグ1件ごとにSELECTとINSERTを繰り返さないようにするため）
        $tagIdByName = Tag::whereIn('name', $tagNames)->pluck('id', 'name');

        // 未登録のタグ名だけを抽出する
        $newTagNames = $tagNames->diff($tagIdByName->keys())->values();

        // すべて登録済みなら、取得済みのIDをそのまま返して追加のクエリを発行しない
        if ($newTagNames->isEmpty()) {
            return $tagIdByName->values()->all();
        }

        // 未登録のタグを1回のINSERTでまとめて登録する。
        // Tag::insert はモデルを経由しないためタイムスタンプが自動設定されず、created_at / updated_at を明示する。
        // 同名タグを同時に登録しようとした場合は tags.name のUNIQUE制約違反となり、呼び出し元のトランザクションごとロールバックされる。
        $now = now();
        Tag::insert($newTagNames->map(fn (string $name): array => [
            'name' => $name,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());

        // 登録済みになったタグのIDをまとめて取得する（insert は採番されたIDを返さないため）
        return Tag::whereIn('name', $tagNames)->pluck('id')->all();
    }
}
