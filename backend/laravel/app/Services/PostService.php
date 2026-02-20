<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
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
        return Post::with(['user', 'tags', 'comments.user']) // ユーザー・タグ・コメント情報を含める
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
        if (array_key_exists('image', $validated) && $validated['image']) {
            // 送信された画像ファイルを取得し、バイナリ化
            $imageData = file_get_contents($validated['image']->getRealPath());
            // 画像のMIMEタイプ（例: image/jpeg, image/pngなど）を取得
            $imageMime = $validated['image']->getMimeType();
        }

        // フォームに投稿した情報を DB に保存
        $post = Post::create([
            'user_id' => $user->id,
            'content' => $validated['content'] ?? null,
            'image_data' => $imageData,
            'image_mime' => $imageMime,
        ]);

        // タグの紐付け
        $post->tags()->sync($this->resolveTagIds($validated['tags'] ?? null));

        return $post->load(['user', 'tags']);
    }

    /**
     * 投稿を削除する
     *
     * @param Post $post 対象の投稿
     * @return Post 削除された投稿（ユーザー情報込み）
     */
    public function delete(Post $post): Post
    {
        $post->load(['user', 'tags', 'comments.user']); // 関連情報を含める
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

        if (array_key_exists('tags', $validated)) {
            $post->tags()->sync($this->resolveTagIds($validated['tags']));
        }

        return $post->load(['user', 'tags', 'comments.user']);
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

        return Post::with(['user', 'tags', 'comments.user']) // ユーザー・タグ・コメント情報を含める
            ->orderByDesc('created_at') // 作成日が新しい順に並べ替え
            ->where('content', 'LIKE', "%{$keyword}%") // 投稿データの本文に部分一致するデータを抽出
            ->get(); // 一致データを取得
    }

    /**
     * コメントを作成する
     *
     * @param Post $post 対象の投稿
     * @param User $user コメント投稿者
     * @param string $content コメント本文
     * @return Comment 作成されたコメント
     */
    public function createComment(Post $post, User $user, string $content): Comment
    {
        $comment = $post->comments()->create([
            'user_id' => $user->id,
            'content' => $content,
        ]);

        return $comment->load('user');
    }

    /**
     * コメントを削除する
     *
     * @param Comment $comment 対象コメント
     * @return Comment 削除されたコメント
     */
    public function deleteComment(Comment $comment): Comment
    {
        $comment->load('user');
        $comment->delete();

        return $comment;
    }

    /**
     * カンマ区切りのタグ文字列を tag_id 配列に変換する
     *
     * @param string|null $rawTags 例: "Laravel,React,API"
     * @return array<int, int> tag_id の配列
     */
    private function resolveTagIds(?string $rawTags): array
    {
        if (! $rawTags) {
            return [];
        }

        $tagNames = collect(explode(',', $rawTags))
            ->map(fn($name) => trim($name))
            ->filter() // 空文字を除外
            ->unique() // 重複を除外
            ->take(10) // 最大10件までに制限
            ->values(); // キーを 0,1,2,... に振り直す

        if ($tagNames->isEmpty()) {
            return [];
        }

        $tagIds = [];
        foreach ($tagNames as $name) {
            // タグ名が既に存在する場合はそのIDを、存在しない場合は新規作成してIDを取得
            $tag = Tag::firstOrCreate(['name' => $name]);
            $tagIds[] = $tag->id;
        }

        return $tagIds;
    }
}
