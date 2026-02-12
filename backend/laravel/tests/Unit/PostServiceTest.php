<?php

namespace Tests\Unit;

use App\Models\Post;
use App\Models\User;
use App\Services\PostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
            'email' => 'user_' . Str::random(10) . '@example.com',
            'password' => Hash::make('password'),
        ]);
        $service = new PostService();

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
     * キーワード未指定の場合は空コレクションになることを確認する
     */
    public function test_search_returns_empty_collection_when_keyword_is_null(): void
    {
        // 検索キーワードが無いケースを想定する
        $service = new PostService();

        // 空のコレクションが返ることを確認する
        $this->assertTrue($service->search(null)->isEmpty());
    }

    /**
     * キーワードに一致する投稿のみ取得できることを確認する
     */
    public function test_search_returns_matching_posts(): void
    {
        // 投稿データを用意する
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'user_' . Str::random(10) . '@example.com',
            'password' => Hash::make('password'),
        ]);
        Post::create(['user_id' => $user->id, 'content' => '数学を勉強中']);
        Post::create(['user_id' => $user->id, 'content' => '英語を勉強中']);

        $service = new PostService();

        // 検索結果が一致するものだけになることを確認する
        $results = $service->search('数学');
        $this->assertCount(1, $results);
        $this->assertSame('数学を勉強中', $results->first()->content);
    }

    /**
     * 投稿更新時に本文が反映されることを確認する
     */
    public function test_update_changes_content_and_loads_user(): void
    {
        // 更新対象の投稿を用意する
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'user_' . Str::random(10) . '@example.com',
            'password' => Hash::make('password'),
        ]);
        $post = Post::create(['user_id' => $user->id, 'content' => '更新前の本文']);

        $service = new PostService();
        $updated = $service->update($post, ['content' => '更新後の本文']);

        // 更新結果が反映され、ユーザー情報も読み込まれることを確認する
        $this->assertSame('更新後の本文', $updated->content);
        $this->assertTrue($updated->relationLoaded('user'));
        $this->assertSame($user->id, $updated->user->id);
    }

    /**
     * 投稿削除後にデータが残らないことを確認する
     */
    public function test_delete_removes_post_and_returns_user(): void
    {
        // 削除対象の投稿を用意する
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'user_' . Str::random(10) . '@example.com',
            'password' => Hash::make('password'),
        ]);
        $post = Post::create(['user_id' => $user->id, 'content' => '削除対象']);

        $service = new PostService();
        $deleted = $service->delete($post);

        // 削除後にDBから消えていることを確認する
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        $this->assertTrue($deleted->relationLoaded('user'));
        $this->assertSame($user->id, $deleted->user->id);
    }
}
