<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;

// アカウント登録処理
Route::post('/register', [AuthController::class, 'register']);

// ログイン認証処理
Route::post('/login', [AuthController::class, 'login']);

// 有効なトークンであるか確認
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'loginSuccess']); // ユーザー名を取得
    Route::get('/posts', [PostController::class, 'index']); // 投稿一覧を取得
    Route::post('/posts', [PostController::class, 'store']); // 投稿内容を保存
    Route::delete('/posts/{post}', [PostController::class, 'destroy']); // 投稿を削除

});
