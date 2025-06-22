<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

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

        // 列名「name」に一致する行を取得
        $user = User::where('name', $request->name)->first();

        // ユーザーが存在しない、またはパスワードが一致しない場合
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'name' => ['The credentials are incorrect.'],
            ]);
        }

        // 認証成功 → トークンを発行して返す（トークンはDBに保存される）
        return response()->json([
            'token' => $user->createToken('react')->plainTextToken,
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

        // 認証済みならユーザー名を返す
        if ($user) {
            return response()->json([
                'name' => $user->name,
            ]);
        } else {
            // 未認証なら401エラーを返す
            return response()->json(['message' => '未認証'], 401);
        }
    }
}
