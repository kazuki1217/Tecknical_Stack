<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\PostStoreRequest;
use App\Http\Requests\PostUpdateRequest;

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
        Log::info('[投稿一覧] 処理を開始します。');

        try {
            // 全ての投稿データを取得
            $posts = Post::with('user') // ユーザー情報を含める
                ->orderByDesc('created_at') // 作成日が新しい順番に並び替え
                ->get();

            Log::debug("[投稿一覧] 取得したデータ", $posts->toArray());
            Log::info('[投稿一覧] データの取得に成功しました。', ['実行したユーザーID' => Auth::user()->id]);
            return response()->json(['message' => '全ての投稿データを取得しました。', 'data' => $posts], 200);
        } catch (\Throwable $e) {
            Log::error('[投稿一覧] 想定外のエラーが発生しました。', ['エラー内容' => $e->getMessage(), 'ファイル名' => $e->getFile(), '行番号' => $e->getLine()]);
            return response()->json(['message' => 'サーバー側でエラーが発生しました。'], 500);
        }
    }

    /**
     * 投稿データを作成
     *
     * @param PostStoreRequest $request 投稿データの本文または画像ファイルを含むリクエスト
     * @return \Illuminate\Http\JsonResponse 成功時は登録された投稿情報を返し、失敗時は失敗メッセージを返す
     */
    public function store(PostStoreRequest $request)
    {
        Log::info('[投稿作成] 処理を開始します。');

        try {
            $validated = $request->validated();

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

            Log::info('[投稿作成] データの作成に成功しました。', ['実行したユーザーID' => Auth::user()->id]);
            return response()->json(['message' => '投稿データを作成しました。', 'data' => $post->load('user')], 201);
        } catch (\Throwable $e) {
            Log::error('[投稿作成] 想定外のエラーが発生しました。', ['エラー内容' => $e->getMessage(), 'ファイル名' => $e->getFile(), '行番号' => $e->getLine()]);
            return response()->json(['message' => 'サーバー側でエラーが発生しました。'], 500);
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
        Log::info('[投稿削除] 処理を開始します。');

        try {
            // トークン認証されたユーザー情報を取得
            $user = Auth::user();

            // 投稿者本人の投稿データではない場合
            if ($post->user_id !== $user->id) {
                return response()->json(['message' => '投稿者本人の投稿データではないため、削除できません。'], 403);
            }

            $post->load('user'); // 投稿データにユーザー情報を含める
            $post->delete(); // 投稿データを削除

            Log::info('[投稿削除] データの削除に成功しました。', ['実行したユーザーID' => Auth::user()->id]);
            return response()->json(['message' => '投稿データを削除しました。', 'data' => $post], 200);
        } catch (\Throwable $e) {
            Log::error('[投稿削除] 想定外のエラーが発生しました。', ['エラー内容' => $e->getMessage(), 'ファイル名' => $e->getFile(), '行番号' => $e->getLine()]);
            return response()->json(['message' => 'サーバー側でエラーが発生しました。'], 500);
        }
    }

    /**
     * 投稿データを更新
     *
     * @param PostUpdateRequest $request 更新された本文情報を含むリクエスト
     * @param \App\Models\Post $post 対象の投稿（ルートモデルバインディングにより自動で取得）
     * @return \Illuminate\Http\JsonResponse 成功時は更新した投稿を返し、失敗時は失敗メッセージを返す
     */
    public function update(PostUpdateRequest $request, Post $post)
    {
        Log::info('[投稿更新] 処理を開始します。');

        try {
            // トークン認証されたユーザー情報を取得
            $user = Auth::user();

            // 投稿者本人の投稿データではない場合
            if ($post->user_id !== $user->id) {
                return response()->json(['message' => '投稿者本人の投稿データではないため、許可されていません。'], 403);
            }

            $validated = $request->validated();

            // 投稿データを更新
            $post->content = $validated['content'];
            $post->save();

            Log::info('[投稿更新] データの更新に成功しました。', ['実行したユーザーID' => Auth::user()->id]);
            return response()->json(['message' => '投稿データを更新しました。', 'data' => $post->load('user')], 200);
        } catch (\Throwable $e) {
            Log::error('[投稿更新] 想定外のエラーが発生しました。', ['エラー内容' => $e->getMessage(), 'ファイル名' => $e->getFile(), '行番号' => $e->getLine()]);
            return response()->json(['message' => 'サーバー側でエラーが発生しました。'], 500);
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
        Log::info('[投稿検索] 処理を開始します。');

        try {
            $keyword = $request->query('keyword');
            Log::debug('[投稿検索] 検索バーに入力した文字', ['キーワード' => $keyword]);

            // 検索バーに入力した文字が存在した場合
            if ($keyword) {
                // 検索処理を実行
                $posts = Post::with('user') // ユーザー情報を含める
                    ->orderByDesc('created_at') // 作成日が新しい順に並べ替え
                    ->where('content', 'LIKE', "%{$keyword}%") // 投稿データの本文に部分一致するデータを抽出
                    ->get();
            }
            Log::info('[投稿検索] 一致したデータの取得に成功しました。', ['実行したユーザーID' => Auth::user()->id]);
            return response()->json(['message' => 'キーワード検索に一致した投稿データを取得しました。', 'data' => $posts], 200);
        } catch (\Throwable $e) {
            Log::error('[投稿検索] 想定外のエラーが発生しました。', ['エラー内容' => $e->getMessage(), 'ファイル名' => $e->getFile(), '行番号' => $e->getLine()]);
            return response()->json(['message' => 'サーバー側でエラーが発生しました。'], 500);
        }
    }
}
