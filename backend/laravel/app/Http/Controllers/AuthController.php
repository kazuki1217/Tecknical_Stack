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
        Log::info('[アカウント登録] 処理を開始します。');

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
            Log::info('[アカウント登録] 登録に成功しました。', ['ユーザーID' => $user?->id, 'ユーザー名' => $user?->name]);
            return response()->json(['message' => 'アカウント登録が正常に完了しました。'], 201);
        } catch (ValidationException $e) {
            Log::info('[アカウント登録] 入力内容に不備があったため、登録に失敗しました。');
            return response()->json(['message' => '入力内容に誤りがあります。', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('[アカウント登録] 想定外のエラーが発生しました。', ['エラー内容' => $e->getMessage(), 'ファイル名' => $e->getFile(), '行番号' => $e->getLine()]);
            return response()->json(['message' => 'サーバー側でエラーが発生しました。'], 500);
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
        Log::info('[ログイン] 処理を開始します。');

        try {
            // リクエスト元の IPアドレスを取得
            $throttleKey = 'login:' . Str::lower($request->ip());

            // 直近60秒間で5回以上ログインに失敗した場合
            if (RateLimiter::tooManyAttempts($throttleKey, 5)) {

                // 次に試行できるまでの残り秒数を取得
                $seconds = RateLimiter::availableIn($throttleKey);

                Log::warning('[ログイン] 直近60秒での失敗回数が上限に達したため、試行を制限しました。', ['リクエスト元のIPアドレス' => $request->ip(), '制限した時間' => $seconds]);
                return response()->json(["message" => "短時間でのログイン試行回数が多すぎます。{$seconds}秒後に再試行してください。"], 429);
            }

            // バリデーション
            $request->validate([
                'email' => 'required', // 入力必須
                'password' => 'required', // 入力必須
            ]);

            // メールアドレスに一致するデータを1行取得
            $user = User::where('email', $request->email)->first();
            Log::debug('[ログイン] メールアドレスに一致したデータ', ['ユーザーID' => $user->id, 'ユーザー名' => $user->name]);

            // ユーザーが存在しない、またはパスワードが一致しない場合
            if (! $user || ! Hash::check($request->password, $user->password)) {
                RateLimiter::hit($throttleKey, 60); // 失敗した回数を +1 加算
                Log::warning('[ログイン] ユーザーが存在しない、またはパスワードが一致しないため、ログイン認証に失敗しました。', ['ユーザーID' => $user->id, 'ユーザー名' => $user->name]);
                return response()->json(['message' => 'ログイン認証に失敗しました。'], 401);
            }

            // トークンを作成
            $tokenResult = $user->createToken('user_login');
            $token = $tokenResult->accessToken;

            // トークンの有効期限を「10分間」に設定
            $token->expires_at = now()->addSeconds(600);
            $token->save();
            Log::info('[ログイン] 認証に成功しました。', ['ユーザーID' => $user?->id, 'ユーザー名' => $user?->name]);
            return response()->json(['message' => 'ログイン認証が正常に完了しました。', 'data' => ['token' => $tokenResult->plainTextToken, 'name' => $user->name]], 200);
        } catch (ValidationException $e) {
            RateLimiter::hit($throttleKey, 60); // 失敗した回数を +1 加算
            Log::info('[ログイン] 入力内容に不備があったため、認証に失敗しました。');
            return response()->json(['message' => '入力内容に誤りがあります。', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('[ログイン] 想定外のエラーが発生しました。', ['エラー内容' => $e->getMessage(), 'ファイル名' => $e->getFile(), '行番号' => $e->getLine()]);
            return response()->json(['message' => 'サーバー側でエラーが発生しました。'], 500);
        }
    }


    /**
     * ログイン状態のユーザー情報を取得
     *
     * @return \Illuminate\Http\JsonResponse 成功時はユーザー名を返し、失敗時は失敗メッセージを返す
     */
    public function loginSuccess()
    {
        Log::info('[ログインユーザー] 処理を開始します。');

        try {
            // トークン認証されたユーザー情報を取得
            $user = Auth::user();
            Log::info('[ログインユーザー] ユーザー名の取得に成功しました。', ['実行したユーザーID' => $user?->id]);
            return response()->json(['message' => 'ログイン状態のユーザー情報を取得しました。', 'data' => ['name' => $user?->name]], 200);
        } catch (\Throwable $e) {
            Log::error('[ログインユーザー] 想定外のエラーが発生しました。', ['エラー内容' => $e->getMessage(), 'ファイル名' => $e->getFile(), '行番号' => $e->getLine()]);
            return response()->json(['message' => 'サーバー側でエラーが発生しました。'], 500);
        }
    }
}
