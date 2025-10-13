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
     * 全ての投稿データを取得
     *
     * @return \Illuminate\Http\JsonResponse 成功時は投稿一覧を返し、失敗時は失敗メッセージを返す
     */
    public function index()
    {
        try {
            // 全ての投稿データを取得
            $posts = Post::with('user') // ユーザー情報を含める
                ->orderByDesc('created_at') // 作成日が新しい順番に並び替え
                ->get();

            return response()->json(['message' => '全ての投稿データを取得しました。', 'data' => $posts], 200);
        } catch (\Throwable $e) {
            Log::error('全ての投稿データを取得する処理において、予期せぬエラーが発生しました。', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json(['message' => '予期せぬエラーが発生しました。'], 500);
        }
    }

    /**
     * 投稿データを作成
     *
     * @param \Illuminate\Http\Request $request 投稿データの本文または画像ファイルを含むリクエスト
     * @return \Illuminate\Http\JsonResponse 成功時は登録された投稿情報を返し、失敗時は失敗メッセージを返す
     */
    public function store(Request $request)
    {
        try {
            // バリデーション
            $validated = $request->validate([
                'content' => 'nullable|string|max:1000', // 入力は任意 | 文字列であること | 1000文字以下であること
                'image' => 'nullable|image|max:2048', // 入力は任意 | 画像ファイルであること | 2048KB以下であること
            ]);

            // 本文と画像ファイルの両方が存在しない場合
            if (empty($validated['content']) && !$request->hasFile('image')) {
                return response()->json(['message' => '本文または画像のいずれかを入力してください。'], 422);
            }

            // 画像データとMIMEタイプを初期化
            $imageData = null;
            $imageMime = null;

            // 画像ファイルが存在する場合
            if ($request->hasFile('image')) {

                $imageFile = $request->file('image'); // 送信された画像ファイルを取得
                $imageData = file_get_contents($imageFile->getRealPath()); // 画像ファイルの中身をバイナリデータとして読み込む
                $imageMime = $imageFile->getMimeType(); // 画像のMIMEタイプ（例: image/jpeg, image/pngなど）を取得
            }

            // フォームに投稿した情報を DB に保存
            $post = Post::create([
                'user_id' => $request->user()->id,
                'content' => $validated['content'] ?? null,
                'image_data' => $imageData,
                'image_mime' => $imageMime,
            ]);

            return response()->json(['message' => '投稿データを作成しました。', 'data' => $post->load('user')], 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => '入力内容に誤りがあります。', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('投稿内容を作成する処理において、予期せぬエラーが発生しました。', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json(['message' => '予期せぬエラーが発生しました。'], 500);
        }
    }

    /**
     * 投稿データを削除
     *
     * @param \App\Models\Post $post 対象の投稿（ルートモデルバインディングにより自動で取得）
     * @return \Illuminate\Http\JsonResponse 成功時は削除結果メッセージを返し、失敗時は失敗メッセージを返す
     */
    public function destroy(Post $post)
    {
        try {
            // トークン認証されたユーザー情報を取得
            $user = Auth::user();

            // 投稿者本人の投稿データではない場合
            if ($post->user_id !== $user->id) {
                return response()->json(['message' => '投稿者本人の投稿データではないため、削除できません。'], 403);
            }

            // 投稿データを削除
            $post->delete();

            return response()->json(['message' => '投稿データを削除しました。'], 200);
        } catch (\Throwable $e) {
            Log::error('投稿データを削除する処理において、予期せぬエラーが発生しました。', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json(['message' => '予期せぬエラーが発生しました。'], 500);
        }
    }

    /**
     * 投稿データを更新
     *
     * @param \Illuminate\Http\Request $request 更新された本文情報を含むリクエスト
     * @param \App\Models\Post $post 対象の投稿（ルートモデルバインディングにより自動で取得）
     * @return \Illuminate\Http\JsonResponse 成功時は更新した投稿を返し、失敗時は失敗メッセージを返す
     */
    public function update(Request $request, Post $post)
    {
        try {
            // トークン認証されたユーザー情報を取得
            $user = Auth::user();

            // 投稿者本人の投稿データではない場合
            if ($post->user_id !== $user->id) {
                return response()->json(['message' => '投稿者本人の投稿データではないため、許可されていません。'], 403);
            }

            // バリデーション
            $validated = $request->validate([
                'content' => 'required|string|max:1000', // 入力必須 | 文字列であること | 1000文字以下であること
            ]);

            // 投稿データを更新
            $post->content = $validated['content'];
            $post->save();

            return response()->json(['message' => '投稿データを更新しました。', 'post' => $post->load('user')], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => '入力内容に誤りがあります。', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('投稿データを更新する処理において、予期せぬエラーが発生しました。', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json(['message' => '予期せぬエラーが発生しました。'], 500);
        }
    }

    /**
     * 投稿データを検索
     *
     * @param \Illuminate\Http\Request $request 検索バーに入力した文字情報を含むリクエスト
     * @return \Illuminate\Http\JsonResponse 成功時はヒットした投稿を返し、失敗時は失敗メッセージを返す
     */
    public function search(Request $request)
    {
        try {
            // 検索バーに入力した文字が存在した場合
            $keyword = $request->query('keyword');
            if ($keyword) {
                // 検索処理を実行
                $posts = Post::with('user') // ユーザー情報を含める
                    ->orderByDesc('created_at') // 作成日が新しい順に並べ替え
                    ->where('content', 'LIKE', "%{$keyword}%") // 投稿データの本文に部分一致するデータを抽出
                    ->get();
            }
            return response()->json(['message' => 'キーワード検索に一致した投稿データを取得しました。', 'post' => $posts], 200);
        } catch (\Throwable $e) {
            Log::error('投稿データを検索する処理において、予期せぬエラーが発生しました。', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json(['message' => '投稿を検索する処理で、予期せぬエラーが発生しました。'], 500);
        }
    }
}
