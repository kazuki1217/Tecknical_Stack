<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

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
    Route::patch('/posts/{post}', [PostController::class, 'update']); // 投稿を編集
    Route::get('/posts/search', [PostController::class, 'search']); // 投稿を検索
    Route::post('/posts/{post}/comments', [PostController::class, 'storeComment']); // 投稿にコメントを追加
    Route::delete('/comments/{comment}', [PostController::class, 'destroyComment']); // コメントを削除
});

// LaravelとMySQLの稼働状態を個別に確認
Route::get('/health/live', [HealthController::class, 'live']);
Route::get('/health/ready', [HealthController::class, 'ready']);
