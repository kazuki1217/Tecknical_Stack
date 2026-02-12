<?php

namespace Tests\Feature;

use App\Models\Post;
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
     * 認証済みユーザーが投稿を作成できることを確認する
     */
    public function test_authenticated_user_can_create_post(): void
    {
        // 認証済みユーザーを用意する
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'user_' . Str::random(10) . '@example.com',
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
            'email' => 'user_' . Str::random(10) . '@example.com',
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
    public function test_user_cannot_delete_others_post(): void
    {
        // 投稿者と別ユーザーを用意する
        $owner = User::create([
            'name' => 'オーナー',
            'email' => 'owner_' . Str::random(10) . '@example.com',
            'password' => Hash::make('password'),
        ]);
        $other = User::create([
            'name' => '他ユーザー',
            'email' => 'other_' . Str::random(10) . '@example.com',
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
}
