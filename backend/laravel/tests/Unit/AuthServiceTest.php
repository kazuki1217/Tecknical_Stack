<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * AuthService のユニットテスト
 */
class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 登録処理でユーザーが作成されることを確認する
     */
    public function test_register_creates_user_with_hashed_password(): void
    {
        // 登録情報を用意する
        $service = new AuthService();
        $payload = [
            'name' => 'テストユーザー',
            'email' => 'user_' . Str::random(10) . '@example.com',
            'password' => 'password',
        ];

        // 登録処理を実行する
        $user = $service->register($payload);

        // DBに保存され、パスワードがハッシュ化されていることを確認する
        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => $payload['email']]);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    /**
     * ログイン成功時にユーザーとトークンが返ることを確認する
     */
    public function test_attempt_login_returns_user_and_token_on_success(): void
    {
        // 事前にユーザーを作成する
        $email = 'user_' . Str::random(10) . '@example.com';
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => $email,
            'password' => Hash::make('password'),
        ]);

        $service = new AuthService();

        // ログイン認証を実行する
        $result = $service->attemptLogin($email, 'password');

        // 成功時の戻り値を確認する
        $this->assertNotNull($result);
        $this->assertSame($user->id, $result['user']->id);
        $this->assertNotEmpty($result['plainTextToken']);

        // トークンがDBに保存されていることを確認する
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id]);
    }

    /**
     * 認証失敗時に null が返ることを確認する
     */
    public function test_attempt_login_returns_null_on_failure(): void
    {
        // ユーザーを作成する
        $email = 'user_' . Str::random(10) . '@example.com';
        User::create([
            'name' => 'テストユーザー',
            'email' => $email,
            'password' => Hash::make('password'),
        ]);

        $service = new AuthService();

        // パスワード不一致のケース
        $this->assertNull($service->attemptLogin($email, 'wrong-password'));

        // 存在しないメールアドレスのケース
        $this->assertNull($service->attemptLogin('missing_' . Str::random(10) . '@example.com', 'password'));
    }
}
