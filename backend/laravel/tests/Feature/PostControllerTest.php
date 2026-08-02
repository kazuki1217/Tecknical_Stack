<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * 投稿APIのFeatureテスト
 */
class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 投稿一覧が新着順で20件ずつ取得でき、関連情報とページ情報を返すことを確認する
     */
    public function test_index_returns_paginated_posts_with_relations(): void
    {
        // 認証済みユーザーと投稿に紐づく関連データを用意する
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'user_'.Str::random(10).'@example.com',
            'password' => Hash::make('password'),
        ]);
        $tag = Tag::create(['name' => 'Laravel']);

        $posts = collect();
        for ($i = 1; $i <= 25; $i++) {
            // 作成日時をずらして、新着順の並びをテストしやすくする
            $post = Post::create([
                'user_id' => $user->id,
                'content' => "投稿{$i}",
                'created_at' => now()->addSeconds($i),
                'updated_at' => now()->addSeconds($i),
            ]);
            $post->tags()->attach($tag->id);
            $posts->push($post);
        }
        Comment::create([
            'post_id' => $posts->last()->id,
            'user_id' => $user->id,
            'content' => '最新投稿へのコメント',
        ]);
        Sanctum::actingAs($user);

        // 1ページ目は新しい投稿から20件だけ返ることを確認する
        $firstPageResponse = $this->getJson('/api/posts');

        $firstPageResponse->assertStatus(200)
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('data.0.content', '投稿25')
            ->assertJsonPath('data.0.user.id', $user->id)
            ->assertJsonPath('data.0.tags.0.name', 'Laravel')
            ->assertJsonPath('data.0.comments.0.content', '最新投稿へのコメント')
            ->assertJsonPath('data.0.comments.0.user.id', $user->id)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.has_more_pages', true);

        // 2ページ目は残り5件を返すことを確認する
        $secondPageResponse = $this->getJson('/api/posts?page=2');

        $secondPageResponse->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('data.0.content', '投稿5')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.has_more_pages', false);
    }

    /**
     * 投稿検索が新着順で20件ずつ取得でき、関連情報とページ情報を返すことを確認する
     */
    public function test_search_returns_paginated_posts_with_relations(): void
    {
        // SQLiteでも検証できるハッシュタグ検索用に、認証済みユーザーと関連データを用意する
        $user = User::create([
            'name' => '検索ユーザー',
            'email' => 'user_'.Str::random(10).'@example.com',
            'password' => Hash::make('password'),
        ]);
        $tag = Tag::create(['name' => '検索対象']);

        $posts = collect();
        for ($i = 1; $i <= 25; $i++) {
            $post = Post::create([
                'user_id' => $user->id,
                'content' => "検索投稿{$i}",
                'created_at' => now()->addSeconds($i),
                'updated_at' => now()->addSeconds($i),
            ]);
            $post->tags()->attach($tag->id);
            $posts->push($post);
        }
        Comment::create([
            'post_id' => $posts->last()->id,
            'user_id' => $user->id,
            'content' => '最新検索投稿へのコメント',
        ]);
        Sanctum::actingAs($user);

        // 1ページ目は新しい投稿から20件だけ返ることを確認する
        $firstPageResponse = $this->getJson('/api/posts/search?content=%23検索対象');

        $firstPageResponse->assertStatus(200)
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('data.0.content', '検索投稿25')
            ->assertJsonPath('data.0.user.id', $user->id)
            ->assertJsonPath('data.0.tags.0.name', '検索対象')
            ->assertJsonPath('data.0.comments.0.content', '最新検索投稿へのコメント')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.has_more_pages', true);

        // 2ページ目は残り5件を返すことを確認する
        $secondPageResponse = $this->getJson('/api/posts/search?content=%23検索対象&page=2');

        $secondPageResponse->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('data.0.content', '検索投稿5')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.has_more_pages', false);
    }

    /**
     * 認証済みユーザーが投稿を作成できることを確認する
     */
    public function test_authenticated_user_can_create_post(): void
    {
        // 認証済みユーザーを用意する
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'user_'.Str::random(10).'@example.com',
            'password' => Hash::make('password'),
        ]);
        Sanctum::actingAs($user);

        // 投稿作成APIを呼び出す
        $response = $this->postJson('/api/posts', [
            'content' => 'テスト投稿',
            'image' => null,
        ]);

        // 成功レスポンスとDB保存を確認する
        $response->assertStatus(201)
            ->assertJsonPath('data.content', 'テスト投稿')
            ->assertJsonPath('data.user.id', $user->id);

        // DBに期待通りのデータが保存されているか確認する
        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'content' => 'テスト投稿',
        ]);
    }

    /**
     * 投稿者本人が投稿を更新できることを確認する
     */
    public function test_authenticated_user_can_update_own_post(): void
    {
        // 既存投稿を準備する
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'user_'.Str::random(10).'@example.com',
            'password' => Hash::make('password'),
        ]);
        $post = Post::create(['user_id' => $user->id, 'content' => '更新前']);
        Sanctum::actingAs($user);

        // 投稿更新APIを呼び出す
        $response = $this->patchJson("/api/posts/{$post->id}", [
            'content' => '更新後',
        ]);

        // 成功レスポンスとDB反映を確認する
        $response->assertStatus(200)
            ->assertJsonPath('data.content', '更新後')
            ->assertJsonPath('data.user.id', $user->id);

        // DBに期待通りのデータが保存されているか確認する
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'content' => '更新後',
        ]);
    }

    /**
     * 投稿者以外が削除しようとすると拒否されることを確認する
     */
    public function test_user_cannot_delete_same_named_users_post(): void
    {
        // 投稿者と別ユーザーを用意する
        $owner = User::create([
            'name' => '同名ユーザー',
            'email' => 'owner_'.Str::random(10).'@example.com',
            'password' => Hash::make('password'),
        ]);
        $other = User::create([
            'name' => '同名ユーザー',
            'email' => 'other_'.Str::random(10).'@example.com',
            'password' => Hash::make('password'),
        ]);
        $post = Post::create(['user_id' => $owner->id, 'content' => '他人の投稿']);
        Sanctum::actingAs($other);

        // 投稿削除APIを呼び出す
        $response = $this->deleteJson("/api/posts/{$post->id}");

        // 403が返り、データが残ることを確認する
        $response->assertStatus(403)
            ->assertJson(['message' => '投稿者本人の投稿データではないため、削除できません。']);

        // DBに期待通りのデータが保存されているか確認する
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    /**
     * 同名の別ユーザーが他人のコメントを削除できないことを確認する
     */
    public function test_user_cannot_delete_same_named_users_comment(): void
    {
        // 表示名が同じでもIDが異なる2ユーザーを用意する
        $owner = User::create([
            'name' => '同名ユーザー',
            'email' => 'owner_'.Str::random(10).'@example.com',
            'password' => Hash::make('password'),
        ]);
        $other = User::create([
            'name' => '同名ユーザー',
            'email' => 'other_'.Str::random(10).'@example.com',
            'password' => Hash::make('password'),
        ]);
        $post = Post::create(['user_id' => $owner->id, 'content' => 'コメント対象']);
        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $owner->id,
            'content' => '削除できないコメント',
        ]);
        Sanctum::actingAs($other);

        // 同名でもユーザーIDが異なるため削除を拒否する
        $response = $this->deleteJson("/api/comments/{$comment->id}");

        $response->assertStatus(403)
            ->assertJson(['message' => '投稿者本人のコメントではないため、削除できません。']);
        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }
}
