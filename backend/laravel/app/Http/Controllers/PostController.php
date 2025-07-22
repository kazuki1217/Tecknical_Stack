<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * 投稿関連の処理をまとめたコントローラ
 */
class PostController extends Controller
{
    /**
     * 投稿一覧を取得
     *
     * @return \Illuminate\Http\JsonResponse 成功時は投稿一覧を返し、失敗時は失敗メッセージを返す
     */
    public function index()
    {
        try {
            // ユーザー情報を含めて新しい順に取得
            $posts = Post::with('user')
                ->orderByDesc('created_at')
                ->get();

            return response()->json([
                'message' => '投稿一覧の取得に成功しました。',
                'data' => $posts,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('投稿一覧の取得時に、予期せぬエラーが発生しました。', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),]);
            return response()->json(['message' => '投稿一覧の取得時に、予期せぬエラーが発生しました。',], 500);
        }
    }

    /**
     * 新規投稿の登録処理
     *
     * @param \Illuminate\Http\Request $request content, image を含むリクエスト
     * @return \Illuminate\Http\JsonResponse 成功時は登録された投稿情報を返し、失敗時は失敗メッセージを返す
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'content' => 'nullable|string|max:1000',
                'image' => 'nullable|image|max:2048',
            ]);

            if (empty($validated['content']) && !$request->hasFile('image')) {
                return response()->json(['message' => 'テキストまたは画像のいずれかを入力してください。'], 422);
            }

            $imageData = null;
            $imageMime = null;

            if ($request->hasFile('image')) {
                $imageFile = $request->file('image');
                $imageData = file_get_contents($imageFile->getRealPath());
                $imageMime = $imageFile->getMimeType();
            }

            $post = Post::create([
                'user_id' => $request->user()->id,
                'content' => $validated['content'] ?? null,
                'image_data' => $imageData,
                'image_mime' => $imageMime,
            ]);

            return response()->json([
                'message' => '新規投稿の登録処理に成功しました。',
                'data' => $post->load('user'),
            ], 201);
        } catch (ValidationException $e) {
            Log::info('新規投稿の登録処理で、入力内容に誤りがありました。', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),]);
            return response()->json(['message' => '入力内容に誤りがあります。', 'errors' => $e->errors(),], 422);
        } catch (\Throwable $e) {
            Log::error('新規投稿の登録処理で、予期せぬエラーが発生しました。', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),]);
            return response()->json(['message' => '新規投稿の登録処理で、予期せぬエラーが発生しました。',], 500);
        }
    }

    /**
     * 投稿の削除処理
     *
     * @param \App\Models\Post $post 対象の投稿（ルートモデルバインディングにより自動で取得）
     * @return \Illuminate\Http\JsonResponse 成功時は削除結果メッセージを返し、失敗時は失敗メッセージを返す
     */
    public function destroy(Post $post)
    {
        try {
            $user = Auth::user();

            // 投稿者本人のみ削除可能にする
            if ($post->user_id !== $user->id) {
                return response()->json(['message' => '許可されていません。'], 403);
            }

            $post->delete();

            return response()->json(['message' => '投稿を削除しました。'], 200);
        } catch (\Throwable $e) {
            Log::error('投稿の削除処理で、予期せぬエラーが発生しました。', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),]);
            return response()->json(['message' => '投稿の削除処理で、予期せぬエラーが発生しました。',], 500);
        }
    }

    /**
     * 投稿の更新処理
     *
     * @param \Illuminate\Http\Request $request content を含むリクエスト
     * @param \App\Models\Post $post 対象の投稿（ルートモデルバインディングにより自動で取得）
     * @return \Illuminate\Http\JsonResponse 成功時は更新した投稿を返し、失敗時は失敗メッセージを返す
     */
    public function update(Request $request, Post $post)
    {
        try {
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
            ], 200);
        } catch (ValidationException $e) {
            Log::info('投稿の更新処理で、入力内容に誤りがありました。', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),]);
            return response()->json(['message' => '入力内容に誤りがあります。', 'errors' => $e->errors(),], 422);
        } catch (\Throwable $e) {
            Log::error('投稿の更新処理で、予期せぬエラーが発生しました。', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),]);
            return response()->json(['message' => '投稿の更新処理で、予期せぬエラーが発生しました。',], 500);
        }
    }

    /**
     * 投稿を検索する処理
     *
     * @param \Illuminate\Http\Request $request クエリパラメータ keyword を含むリクエスト
     * @return \Illuminate\Http\JsonResponse 成功時はヒットした投稿を返し、失敗時は失敗メッセージを返す
     */
    public function search(Request $request)
    {
        try {
            // 入力した文字列を取得
            $keyword = $request->query('keyword');

            // 部分一致検索
            if ($keyword) {
                $query = Post::with('user')
                    ->orderByDesc('created_at')
                    ->where('content', 'LIKE', "%{$keyword}%");
            }

            $posts = $query->get();

            return response()->json([
                'message' => '投稿を検索する処理に成功しました。',
                'post' => $posts,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('投稿を検索する処理で、予期せぬエラーが発生しました。', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),]);
            return response()->json(['message' => '投稿を検索する処理で、予期せぬエラーが発生しました。',], 500);
        }
    }
}
