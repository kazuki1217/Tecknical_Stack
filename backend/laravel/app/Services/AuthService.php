<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * 認証に関するビジネスロジックを担当するサービス
 */
class AuthService
{
    /**
     * ユーザー登録を行う
     *
     * @param array<string, string> $validated 登録済みバリデーション済み入力
     * @return User 作成されたユーザー
     */
    public function register(array $validated): User
    {
        // ユーザー情報を DB に保存
        return User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);
    }

    /**
     * ログイン認証を試み、成功時はユーザーとトークンを返す
     *
     * @param string $email メールアドレス
     * @param string $password パスワード
     * @return array{user: User, plainTextToken: string}|null 認証成功時のユーザーとトークン
     */
    public function attemptLogin(string $email, string $password): ?array
    {
        // メールアドレスに一致するデータを1行取得
        $user = User::where('email', $email)->first();

        // ユーザーが存在しない、またはパスワードが一致しない場合
        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        // トークンを作成
        $tokenResult = $user->createToken('user_login');
        $token = $tokenResult->accessToken;

        // トークンの有効期限を「10分間」に設定
        $token->expires_at = now()->addSeconds(600);
        $token->save();

        return [
            'user' => $user,
            'plainTextToken' => $tokenResult->plainTextToken,
        ];
    }
}
