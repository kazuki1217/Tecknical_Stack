<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;


Route::get('/message', function () {
    return response()->json(['message' => 'Hello from Laravel!']);
});

// アカウント登録（Reactでは、アカウント登録情報を入力した後に使う）
Route::post('/register', [AuthController::class, 'register']);

// ログイン状態の確認とユーザー情報の取得（Reactでは、ベースURLアクセス時に使う）
Route::middleware('auth:sanctum')->get('/user', [AuthController::class, 'loginSuccess']);

// ログイン認証処理（Reactでは、ログイン情報入力後に使う）
Route::post('/login', [AuthController::class, 'login']);

// ログイン状態の確認とユーザー情報の取得（Reactでは、ログイン情報入力後に使う）
Route::middleware('auth:sanctum')->get('/loginsuccess', [AuthController::class, 'loginSuccess']);

// ログイン状態の確認
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/posts', [PostController::class, 'index']); // 投稿一覧を取得
    Route::post('/posts', [PostController::class, 'store']); // 投稿内容を保存
});

