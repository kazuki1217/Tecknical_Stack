<?php

namespace Tests\Unit;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\PostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

/**
 * PostService のユニットテスト
 */
class PostServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 画像付き投稿を作成できることを確認する
     */
    public function test_create_stores_image_data_and_mime(): void
    {
        // 投稿者を用意する
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'user_'.Str::random(10).'@example.com',
            'password' => Hash::make('password'),
        ]);
        $service = new PostService;

        // 画像付き投稿を作成する
        $image = UploadedFile::fake()->create('post.png', 10, 'image/png');
        $post = $service->create($user, [
            'content' => '画像付き投稿',
            'image' => $image,
        ]);

        // 画像情報が保存されていることを確認する
        $this->assertNotNull($post->image_data);
        $this->assertSame($image->getMimeType(), $post->image_mime);
    }

    /**
     * タグ登録が失敗した場合、投稿ごとロールバックされることを確認する
     */
    public function test_create_rolls_back_post_when_tag_registration_fails(): void
    {
        // 投稿者を用意する
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'user_'.Str::random(10).'@example.com',
            'password' => Hash::make('password'),
        ]);
        $service = new PostService;

        // 投稿のINSERT後・タグのINSERT時に失敗する状況を再現する
        $shouldFailTagInsert = true;
        $this->failOnTagInsert($shouldFailTagInsert);

        try {
            // 失敗が呼び出し元まで伝わり、扱っていたデータが例外メッセージに含まれることを確認する
            $this->assertThrows(
                fn () => $service->create($user, ['content' => 'ロールバック対象の投稿', 'tags' => '新規タグ']),
                RuntimeException::class,
                '投稿とタグの登録に失敗しました。',
            );
        } finally {
            // 登録したフックが後続のテストへ影響しないようにする
            $shouldFailTagInsert = false;
        }

        // 投稿・タグ・中間テーブルのいずれにもデータが残っていないことを確認する
        $this->assertDatabaseMissing('posts', ['content' => 'ロールバック対象の投稿']);
        $this->assertDatabaseMissing('tags', ['name' => '新規タグ']);
        $this->assertSame(0, DB::table('post_tag')->count());
    }

    /**
     * 既存タグは再利用し、未登録のタグだけが登録されることを確認する
     */
    public function test_create_reuses_existing_tag_and_registers_only_new_tags(): void
    {
        // 登録済みのタグと未登録のタグが混在する状況を用意する
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'user_'.Str::random(10).'@example.com',
            'password' => Hash::make('password'),
        ]);
        Tag::create(['name' => '既存タグ']);
        $service = new PostService;

        $post = $service->create($user, ['content' => 'タグ付き投稿', 'tags' => '既存タグ,新規タグ']);

        // 指定したタグがすべて紐付き、既存タグが重複登録されていないことを確認する
        $this->assertEqualsCanonicalizing(['既存タグ', '新規タグ'], $post->tags->pluck('name')->all());
        $this->assertSame(2, Tag::count());
    }

    /**
     * タグの件数が増えても、tagsテーブルへのクエリ数が増えないことを確認する
     */
    public function test_create_does_not_issue_more_tag_queries_as_tags_increase(): void
    {
        // タグ以外の条件をそろえるため、同じ投稿者で件数だけを変えて2回投稿する
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'user_'.Str::random(10).'@example.com',
            'password' => Hash::make('password'),
        ]);
        $service = new PostService;

        // タグ2件の投稿で、tagsテーブルへのクエリ数を記録する
        DB::enableQueryLog();
        $service->create($user, ['content' => 'タグ2件の投稿', 'tags' => 'タグA1,タグA2']);
        $tagQueryCountForTwoTags = $this->countTagTableQueries();

        // タグ5件の投稿で、tagsテーブルへのクエリ数を記録する
        DB::flushQueryLog();
        $service->create($user, ['content' => 'タグ5件の投稿', 'tags' => 'タグB1,タグB2,タグB3,タグB4,タグB5']);
        $tagQueryCountForFiveTags = $this->countTagTableQueries();
        DB::disableQueryLog();

        // タグ1件ごとにクエリが増えていないことを確認する
        $this->assertSame($tagQueryCountForTwoTags, $tagQueryCountForFiveTags);
    }

    /**
     * 空文字キーワードの場合は空のページネーション結果になることを確認する
     */
    public function test_search_returns_empty_paginator_when_keyword_is_empty_string(): void
    {
        // 検索キーワードが空文字のケースを想定する
        $service = new PostService;

        // 投稿を含まないページネーション結果が返ることを確認する
        $results = $service->search('');
        $this->assertTrue($results->isEmpty());
        $this->assertSame(0, $results->total());
        $this->assertSame(20, $results->perPage());
    }

    /**
     * 投稿更新時に本文が反映されることを確認する
     */
    public function test_update_changes_content_and_loads_user(): void
    {
        // 更新対象の投稿を用意する
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'user_'.Str::random(10).'@example.com',
            'password' => Hash::make('password'),
        ]);
        $post = Post::create(['user_id' => $user->id, 'content' => '更新前の本文']);

        $service = new PostService;
        $updated = $service->update($post, ['content' => '更新後の本文']);

        // 更新結果が反映され、ユーザー情報も読み込まれることを確認する
        $this->assertSame('更新後の本文', $updated->content);
        $this->assertTrue($updated->relationLoaded('user'));
        $this->assertSame($user->id, $updated->user->id);
    }

    /**
     * タグ登録が失敗した場合、本文とタグの紐付けが更新前のまま残ることを確認する
     */
    public function test_update_rolls_back_content_and_tags_when_tag_registration_fails(): void
    {
        // 本文とタグを持つ更新対象の投稿を用意する
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'user_'.Str::random(10).'@example.com',
            'password' => Hash::make('password'),
        ]);
        $post = Post::create(['user_id' => $user->id, 'content' => '更新前の本文']);
        $existingTag = Tag::create(['name' => '既存タグ']);
        $post->tags()->attach($existingTag->id);

        $service = new PostService;

        // 本文のUPDATE後・タグのINSERT時に失敗する状況を再現する
        $shouldFailTagInsert = true;
        $this->failOnTagInsert($shouldFailTagInsert);

        try {
            // 失敗が呼び出し元まで伝わり、扱っていたデータが例外メッセージに含まれることを確認する
            $this->assertThrows(
                fn () => $service->update($post, ['content' => '更新後の本文', 'tags' => '新規タグ']),
                RuntimeException::class,
                '本文とタグの更新に失敗しました。',
            );
        } finally {
            // 登録したフックが後続のテストへ影響しないようにする
            $shouldFailTagInsert = false;
        }

        // 本文が戻り、sync()のDELETEで外れかけた既存タグの紐付けも残っていることを確認する
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'content' => '更新前の本文']);
        $this->assertDatabaseHas('post_tag', ['post_id' => $post->id, 'tag_id' => $existingTag->id]);
        $this->assertDatabaseMissing('tags', ['name' => '新規タグ']);
    }

    /**
     * 投稿削除後にデータが残らないことを確認する
     */
    public function test_delete_removes_post_and_returns_user(): void
    {
        // 削除対象の投稿を用意する
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'user_'.Str::random(10).'@example.com',
            'password' => Hash::make('password'),
        ]);
        $post = Post::create(['user_id' => $user->id, 'content' => '削除対象']);

        $service = new PostService;
        $deleted = $service->delete($post);

        // 削除後にDBから消えていることを確認する
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        $this->assertTrue($deleted->relationLoaded('user'));
        $this->assertSame($user->id, $deleted->user->id);
    }

    /**
     * 記録済みのクエリログから、tagsテーブルを参照したクエリ数を数える
     *
     * 中間テーブルpost_tagへのINSERTは sync() が紐付け1件ずつ発行するため、tagsテーブルだけを対象にする。
     *
     * @return int tagsテーブルを参照したクエリ数
     */
    private function countTagTableQueries(): int
    {
        return collect(DB::getQueryLog())
            // 識別子の引用符はDBドライバによって異なるため、取り除いてからテーブル名を判定する
            ->filter(fn (array $log): bool => preg_match('/\btags\b/', str_replace(['`', '"'], '', $log['query'])) === 1)
            ->count();
    }

    /**
     * tagsテーブルへのINSERTを失敗させるフックを登録する
     *
     * タグはバルクINSERTで登録しモデルイベントを経由しないため、クエリ実行直前のフックで失敗させる。
     * 登録したフックは取り外せないため、呼び出し側は$enabledをfalseにして無効化する。
     *
     * @param  bool  $enabled  trueの間だけINSERTを失敗させるフラグ（参照渡し）
     */
    private function failOnTagInsert(bool &$enabled): void
    {
        DB::beforeExecuting(function (string $query) use (&$enabled): void {
            // 識別子の引用符はDBドライバによって異なるため、取り除いてからテーブル名を判定する
            $normalizedQuery = str_replace(['`', '"'], '', $query);

            if ($enabled && str_contains($normalizedQuery, 'into tags ')) {
                throw new RuntimeException('タグ登録に失敗しました。');
            }
        });
    }
}
