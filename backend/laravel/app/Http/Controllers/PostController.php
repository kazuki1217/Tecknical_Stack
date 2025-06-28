<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;

/**
 * 投稿関連の処理をまとめたコントローラ
 */
class PostController extends Controller
{
    /**
     * 投稿一覧を取得
     *
     * @return \Illuminate\Http\JsonResponse 投稿データの一覧を返す
     */
    public function index()
    {
        // ユーザー情報を含めて新しい順に取得
        $posts = Post::with('user')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($posts);
    }

    /**
     * 新規投稿の登録処理
     *
     * @param \Illuminate\Http\Request $request content を含むリクエスト
     * @return \Illuminate\Http\JsonResponse 登録された投稿情報を返す
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $post = Post::create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
        ]);

        return response()->json($post->load('user'));
    }
}
