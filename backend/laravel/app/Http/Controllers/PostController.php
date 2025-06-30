<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    /**
     * 投稿の削除処理
     *
     * @param \App\Models\Post $post 
     * @return \Illuminate\Http\JsonResponse 削除結果メッセージを返す
     */
    public function destroy(Post $post)
    {
        $user = Auth::user();

        // 投稿者本人のみ削除可能にする
        if ($post->user_id !== $user->id) {
            return response()->json(['message' => '許可されていません。'], 403);
        }

        $post->delete();

        return response()->json(['message' => '投稿を削除しました。']);
    }

    /**
     * 投稿の更新処理
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Post $post)
    {
        $user = Auth::user();

        // 投稿者本人かチェック
        if ($post->user_id !== $user->id) {
            return response()->json(['message' => '許可されていません。'], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $post->content = $validated['content'];
        $post->save();

        return response()->json([
            'message' => '投稿を更新しました。',
            'post' => $post->load('user'),
        ]);
    }

    /**
     * 投稿を検索する処理
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        // 入力した文字列を取得
        $keyword = $request->query('keyword');

        // 部分一致検索
        if ($keyword) {
            $query = Post::with('user')
                ->orderByDesc('created_at')
                ->where('content', 'LIKE', "%{$keyword}%");
        }

        $posts = $query->get();

        return response()->json($posts);
    }
}
