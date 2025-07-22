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
     * @param Request $request name, email, password, password_confirmation を含むリクエスト
     * @return \Illuminate\Http\JsonResponse 成功時は成功メッセージを返し、失敗時は失敗エラーメッセージを返す
     */
    public function register(Request $request)
    {
        try {
            // 入力バリデーション（name, email, password）
            $request->validate([
                'name' => 'required|string',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:6|confirmed',
            ]);

            // ユーザ情報をDBに保存（パスワードはハッシュ化して保存）
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            return response()->json(['message' => 'アカウント登録が正常に完了しました。'], 201);
        } catch (ValidationException $e) {
            Log::info('アカウント登録処理で、入力内容に誤りがありました。', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),]);
            return response()->json(['message' => '入力内容に誤りがあります。', 'errors' => $e->errors(),], 422);
        } catch (\Throwable $e) {
            Log::error('アカウント登録処理で、予期せぬエラーが発生しました。', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),]);
            return response()->json(['message' => '予期せぬエラーが発生しました。',], 500);
        }
    }


    /**
     * ログイン認証処理
     *
     * @param Request $request name, password, password_confirmation を含むリクエスト
     * @return \Illuminate\Http\JsonResponse 成功時はトークンを返し、失敗時は失敗メッセージを返す
     */
    public function login(Request $request)
    {
        try {
            // バリデーション：nameとpasswordが必須
            $request->validate([
                'name' => 'required',
                'password' => 'required',
            ]);

            $throttleKey = 'login:' . Str::lower($request->ip);

            // ソフトレート制限（スライディングウィンドウ方式）を用いて、直近60秒間で5回以上失敗していたらログイン拒否
            if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
                $seconds = RateLimiter::availableIn($throttleKey);
                Log::info('ログイン試行を制限しました。', ['ip' => $request->ip(), 'name' => $request->name]);
                return response()->json(["message" => "短時間でのログイン試行回数が多すぎます。{$seconds}秒後に再試行してください。"], 429);
            }

            // 列名「name」に一致する行を取得
            $user = User::where('name', $request->name)->first();

            // ユーザーが存在しない、またはパスワードが一致しない場合
            if (! $user || ! Hash::check($request->password, $user->password)) {
                RateLimiter::hit($throttleKey, 60); // ← 60秒保持
                Log::info('ログインに失敗しました。', ['ip' => $request->ip(), 'name' => $request->name,]);
                return response()->json(['message' => 'ログインに失敗しました。'], 401);
            }

            // トークンを作成
            $tokenResult = $user->createToken('react');
            $token = $tokenResult->accessToken;

            // トークンの有効期限を「今から10分後」に設定
            $token->expires_at = now()->addSeconds(600);
            $token->save();

            return response()->json(['message' => 'ログイン認証が正常に完了しました。', 'token' => $tokenResult->plainTextToken, 'name' => $request->name], 200);
        } catch (ValidationException $e) {
            Log::info('ログイン登録処理で、入力内容に誤りがありました。', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),]);
            return response()->json(['message' => '入力内容に誤りがあります。', 'errors' => $e->errors(),], 422);
        } catch (\Throwable $e) {
            Log::error('ログイン登録処理で、予期せぬエラーが発生しました。', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),]);
            return response()->json(['message' => '予期せぬエラーが発生しました。',], 500);
        }
    }


    /**
     * ログイン状態のユーザ情報の取得
     *
     * @return \Illuminate\Http\JsonResponse 成功時はユーザ名を返し、失敗時は失敗メッセージを返す
     */
    public function loginSuccess()
    {
        try {
            $user = Auth::user();
            return response()->json(['message' => 'ログイン状態のユーザ情報を取得しました。', 'name' => $user->name,], 200);
        } catch (\Throwable $e) {
            Log::error('ログイン状態のユーザ情報を取得する際に、予期せぬエラーが発生しました。', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),]);
            return response()->json(['message' => 'ログイン状態のユーザ情報を取得する際に、予期せぬエラーが発生しました。',], 500);
        }
    }
}
