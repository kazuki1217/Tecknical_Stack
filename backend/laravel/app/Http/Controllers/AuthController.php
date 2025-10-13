<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;


/**
 * 認証関連の処理をまとめたコントローラ
 */
class AuthController extends Controller
{
    /**
     * アカウント登録処理
     *
     * @param Request $request 登録情報（名前・メールアドレス・パスワード・パスワード確認）を含むリクエスト
     * @return \Illuminate\Http\JsonResponse 成功時は成功メッセージを返し、失敗時は失敗エラーメッセージを返す
     */
    public function register(Request $request)
    {
        try {
            // バリデーション
            $request->validate([
                'name' => 'required|string', // 入力必須 | 文字列であること
                'email' => 'required|email|unique:users', // 入力必須 | @ を含むメール形式であること | users テーブル内で重複しないこと
                'password' => 'required|min:6|confirmed', // 入力必須 | 6文字以上であること | password_confirmation と一致すること
            ]);

            // ユーザー情報を DB に保存
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password), // パスワードはハッシュ化して保存
            ]);

            return response()->json(['message' => 'アカウント登録が正常に完了しました。'], 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => '入力内容に誤りがあります。', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('アカウント登録処理において、予期せぬエラーが発生しました。', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json(['message' => '予期せぬエラーが発生しました。'], 500);
        }
    }


    /**
     * ログイン認証処理
     *
     * @param Request $request ログイン情報（メールアドレス・パスワード）を含むリクエスト
     * @return \Illuminate\Http\JsonResponse 成功時はトークンを返し、失敗時は失敗メッセージを返す
     */
    public function login(Request $request)
    {
        try {
            // リクエスト元の IPアドレスを取得
            $throttleKey = 'login:' . Str::lower($request->ip());

            // 直近60秒間で5回以上ログインに失敗した場合
            if (RateLimiter::tooManyAttempts($throttleKey, 5)) {

                // 次に試行できるまでの残り秒数を取得
                $seconds = RateLimiter::availableIn($throttleKey);

                Log::warning('ログイン認証処理において、直近60秒間で5回以上ログインに失敗したため、試行を制限してます。', ['ip' => $request->ip(), '名前' => $request->name, '制限した時間' => $seconds]);
                return response()->json(["message" => "短時間でのログイン試行回数が多すぎます。{$seconds}秒後に再試行してください。"], 429);
            }

            // バリデーション
            $request->validate([
                'email' => 'required', // 入力必須
                'password' => 'required', // 入力必須
            ]);

            // メールアドレスに一致するデータを1行取得
            $user = User::where('email', $request->email)->first();

            // ユーザーが存在しない、またはパスワードが一致しない場合
            if (! $user || ! Hash::check($request->password, $user->password)) {
                RateLimiter::hit($throttleKey, 60); // 失敗した回数を +1 加算
                return response()->json(['message' => 'ログインに失敗しました。'], 401);
            }

            // トークンを作成
            $tokenResult = $user->createToken('user_login');
            $token = $tokenResult->accessToken;

            // トークンの有効期限を「10分間」に設定
            $token->expires_at = now()->addSeconds(600);
            $token->save();

            return response()->json(['message' => 'ログイン認証が正常に完了しました。', 'token' => $tokenResult->plainTextToken, 'name' => $user->name], 200);
        } catch (ValidationException $e) {
            RateLimiter::hit($throttleKey, 60); // 失敗した回数を +1 加算
            return response()->json(['message' => '入力内容に誤りがあります。', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('ログイン認証処理において、予期せぬエラーが発生しました。', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json(['message' => '予期せぬエラーが発生しました。'], 500);
        }
    }


    /**
     * ログイン状態のユーザ情報を取得
     *
     * @return \Illuminate\Http\JsonResponse 成功時はユーザ名を返し、失敗時は失敗メッセージを返す
     */
    public function loginSuccess()
    {
        try {
            // トークン認証されたユーザー情報を取得
            $user = Auth::user();
            return response()->json(['message' => 'ログイン状態のユーザ情報を取得しました。', 'name' => $user->name], 200);
        } catch (\Throwable $e) {
            Log::error('トークン認証されたユーザー情報を取得する処理において、予期せぬエラーが発生しました。', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json(['message' => '予期せぬエラーが発生しました。'], 500);
        }
    }
}
