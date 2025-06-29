<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;


/**
 * 認証関連の処理をまとめたコントローラ
 */
class AuthController extends Controller
{
    /**
     * アカウント登録処理
     *
     * @param Request $request name, email, password, password_confirmation を含むリクエスト
     * @return \Illuminate\Http\JsonResponse 作成されたユーザー情報を返す
     */
    public function register(Request $request)
    {
        // 入力バリデーション（name, email, password）
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users', // ユニーク制約あり
            'password' => 'required|min:6|confirmed', // 確認入力 (password_confirmation) も必要
        ]);

        // ハッシュ化してユーザーを作成
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json($user);
    }


    /**
     * ログイン認証処理
     *
     * @param \Illuminate\Http\Request $request nameとpasswordを含むリクエスト
     * @return \Illuminate\Http\JsonResponse 認証用トークンを返す
     */
    public function login(Request $request)
    {
        // バリデーション：nameとpasswordが必須
        $request->validate([
            'name' => 'required',
            'password' => 'required',
        ]);


        $throttleKey = 'login:' . Str::lower($request->ip);

        // ソフトレート制限（スライディングウィンドウ方式）を用いて、直近60秒間で5回以上失敗していたらログイン拒否
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                "status" => "error",
                "message" => "ログイン試行が多すぎます。{$seconds}秒後に再試行してください。"
            ]);
        }

        // 列名「name」に一致する行を取得
        $user = User::where('name', $request->name)->first();

        // ユーザーが存在しない、またはパスワードが一致しない場合
        if (! $user || ! Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey, 60); // ← 60秒保持
            return response()->json([
                'status' => 'error',
                'message' => 'ログインに失敗しました。'
            ]);
        }

        // 認証成功 → トークンを発行して返す（トークンはDBに保存される）
        return response()->json([
            'status' => 'success',
            'token' => $user->createToken('react')->plainTextToken,
            'name' => $request->name
        ]);
    }


    /**
     * ログイン状態の確認とユーザー情報の取得
     *
     * @return \Illuminate\Http\JsonResponse ユーザーのname、または未認証エラーメッセージ
     */
    public function loginSuccess()
    {
        // 現在認証されているユーザーを取得（トークンベース）
        $user = Auth::user();

        return response()->json([
            'name' => $user->name,
        ]);
    }
}
