<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

/**
 * 投稿に関するビジネスロジックを担当するサービス
 */
class PostService
{
    /**
     * 投稿一覧を取得する
     *
     * @return Collection<int, Post> 投稿一覧
     */
    public function getAll(): Collection
    {
        return Post::with('user') // ユーザー情報を含める
            ->orderByDesc('created_at') // 作成日が新しい順番に並び替え
            ->get(); // 全件取得
    }

    /**
     * 投稿を作成する
     *
     * @param User $user 投稿者
     * @param array<string, mixed> $validated バリデーション済み入力
     * @return Post 作成された投稿
     */
    public function create(User $user, array $validated): Post
    {
        // 画像データとMIMEタイプを初期化
        $imageData = null;
        $imageMime = null;

        // 画像ファイルが存在する場合
        if ($validated['image']) {
            // 送信された画像ファイルを取得し、バイナリ化
            $imageData = file_get_contents($validated['image']->getRealPath());
            // 画像のMIMEタイプ（例: image/jpeg, image/pngなど）を取得
            $imageMime = $validated['image']->getMimeType();
        }

        // フォームに投稿した情報を DB に保存
        return Post::create([
            'user_id' => $user->id,
            'content' => $validated['content'] ?? null,
            'image_data' => $imageData,
            'image_mime' => $imageMime,
        ]);
    }

    /**
     * 投稿を削除する
     *
     * @param Post $post 対象の投稿
     * @return Post 削除された投稿（ユーザー情報込み）
     */
    public function delete(Post $post): Post
    {
        $post->load('user'); // 投稿データにユーザー情報を含める
        $post->delete(); // 投稿データを削除

        return $post;
    }

    /**
     * 投稿を更新する
     *
     * @param Post $post 対象の投稿
     * @param array<string, string> $validated バリデーション済み入力
     * @return Post 更新された投稿（ユーザー情報込み）
     */
    public function update(Post $post, array $validated): Post
    {
        // 投稿データを更新
        $post->content = $validated['content'];
        $post->save();

        return $post->load('user');
    }

    /**
     * キーワードで投稿を検索する
     *
     * @param string|null $keyword 検索キーワード
     * @return Collection<int, Post> 検索結果
     */
    public function search(?string $keyword): Collection
    {
        if (! $keyword) {
            return collect();
        }

        return Post::with('user') // ユーザー情報を含める
            ->orderByDesc('created_at') // 作成日が新しい順に並べ替え
            ->where('content', 'LIKE', "%{$keyword}%") // 投稿データの本文に部分一致するデータを抽出
            ->get(); // 一致データを取得
    }
}
