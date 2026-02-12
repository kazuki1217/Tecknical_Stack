<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * AuthController のFeatureテスト
 */
class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * アカウント登録が成功することを確認する
     */
    public function test_register_creates_user(): void
    {
        // 登録リクエストを作成する
        $payload = [
            'name' => 'テストユーザー',
            'email' => 'user_' . Str::random(10) . '@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        // 登録APIを呼び出す
        $response = $this->postJson('/api/register', $payload);

        // 成功レスポンスとDB保存を確認する
        $response->assertStatus(201)
            ->assertJson(['message' => 'アカウント登録が正常に完了しました。']);

        $this->assertDatabaseHas('users', ['email' => $payload['email']]);
    }

    /**
     * ログインが成功するとトークンが返ることを確認する
     */
    public function test_login_returns_token_on_success(): void
    {
        // 事前にユーザーを作成する
        $email = 'user_' . Str::random(10) . '@example.com';
        User::create([
            'name' => 'テストユーザー',
            'email' => $email,
            'password' => Hash::make('password'),
        ]);

        // ログインAPIを呼び出す
        $response = $this->postJson('/api/login', [
            'email' => $email,
            'password' => 'password',
        ]);

        // 成功レスポンスとトークン返却を確認する
        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'テストユーザー')
            ->assertJsonPath('message', 'ログイン認証が正常に完了しました。');

        $this->assertNotEmpty($response->json('data.token'));
    }

    /**
     * ログイン失敗時に401が返ることを確認する
     */
    public function test_login_returns_401_on_failure(): void
    {
        // 事前にユーザーを作成する
        $email = 'user_' . Str::random(10) . '@example.com';
        User::create([
            'name' => 'テストユーザー',
            'email' => $email,
            'password' => Hash::make('password'),
        ]);

        // 誤ったパスワードでログインする
        $response = $this->postJson('/api/login', [
            'email' => $email,
            'password' => 'wrong-password',
        ]);

        // 失敗レスポンスを確認する
        $response->assertStatus(401)
            ->assertJson(['message' => 'ログイン認証に失敗しました。']);
    }

    /**
     * ログイン中ユーザーの情報を取得できることを確認する
     */
    public function test_login_success_returns_user_name(): void
    {
        // 認証済みユーザーを用意する
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'user_' . Str::random(10) . '@example.com',
            'password' => Hash::make('password'),
        ]);

        Sanctum::actingAs($user);

        // ログインユーザー取得APIを呼び出す
        $response = $this->getJson('/api/user');

        // 成功レスポンスとユーザー名を確認する
        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'テストユーザー')
            ->assertJsonPath('message', 'ログイン状態のユーザー情報を取得しました。');
    }
}
