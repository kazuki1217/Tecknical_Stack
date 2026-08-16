<?php

namespace Tests\Unit;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\PostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

/**
 * PostService のユニットテスト
 */
class PostServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 画像付き投稿を作成できることを確認する
     */
    public function test_create_stores_image_data_and_mime(): void
    {
        // 投稿者を用意する
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'user_'.Str::random(10).'@example.com',
            'password' => Hash::make('password'),
        ]);
        $service = new PostService;

        // 画像付き投稿を作成する
        $image = UploadedFile::fake()->create('post.png', 10, 'image/png');
        $post = $service->create($user, [
            'content' => '画像付き投稿',
            'image' => $image,
        ]);

        // 画像情報が保存されていることを確認する
        $this->assertNotNull($post->image_data);
        $this->assertSame($image->getMimeType(), $post->image_mime);
    }

    /**
     * タグ登録が失敗した場合、投稿ごとロールバックされることを確認する
     */
    public function test_create_rolls_back_post_when_tag_registration_fails(): void
    {
        // 投稿者を用意する
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'user_'.Str::random(10).'@example.com',
            'password' => Hash::make('password'),
        ]);
        $service = new PostService;

        // 投稿のINSERT後・タグのINSERT時に失敗する状況を再現する
        Tag::creating(function (): void {
            throw new RuntimeException('タグ登録に失敗しました。');
        });

        try {
            // 失敗が呼び出し元まで伝わり、扱っていたデータが例外メッセージに含まれることを確認する
            $this->assertThrows(
                fn () => $service->create($user, ['content' => 'ロールバック対象の投稿', 'tags' => '新規タグ']),
                RuntimeException::class,
                '投稿とタグの登録に失敗しました。',
            );
        } finally {
            // 登録したリスナーが後続のテストへ残らないようにする
            Tag::flushEventListeners();
        }

        // 投稿・タグ・中間テーブルのいずれにもデータが残っていないことを確認する
        $this->assertDatabaseMissing('posts', ['content' => 'ロールバック対象の投稿']);
        $this->assertDatabaseMissing('tags', ['name' => '新規タグ']);
        $this->assertSame(0, DB::table('post_tag')->count());
    }

    /**
     * 空文字キーワードの場合は空のページネーション結果になることを確認する
     */
    public function test_search_returns_empty_paginator_when_keyword_is_empty_string(): void
    {
        // 検索キーワードが空文字のケースを想定する
        $service = new PostService;

        // 投稿を含まないページネーション結果が返ることを確認する
        $results = $service->search('');
        $this->assertTrue($results->isEmpty());
        $this->assertSame(0, $results->total());
        $this->assertSame(20, $results->perPage());
    }

    /**
     * 投稿更新時に本文が反映されることを確認する
     */
    public function test_update_changes_content_and_loads_user(): void
    {
        // 更新対象の投稿を用意する
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'user_'.Str::random(10).'@example.com',
            'password' => Hash::make('password'),
        ]);
        $post = Post::create(['user_id' => $user->id, 'content' => '更新前の本文']);

        $service = new PostService;
        $updated = $service->update($post, ['content' => '更新後の本文']);

        // 更新結果が反映され、ユーザー情報も読み込まれることを確認する
        $this->assertSame('更新後の本文', $updated->content);
        $this->assertTrue($updated->relationLoaded('user'));
        $this->assertSame($user->id, $updated->user->id);
    }

    /**
     * タグ登録が失敗した場合、本文とタグの紐付けが更新前のまま残ることを確認する
     */
    public function test_update_rolls_back_content_and_tags_when_tag_registration_fails(): void
    {
        // 本文とタグを持つ更新対象の投稿を用意する
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'user_'.Str::random(10).'@example.com',
            'password' => Hash::make('password'),
        ]);
        $post = Post::create(['user_id' => $user->id, 'content' => '更新前の本文']);
        $existingTag = Tag::create(['name' => '既存タグ']);
        $post->tags()->attach($existingTag->id);

        $service = new PostService;

        // 本文のUPDATE後・タグのINSERT時に失敗する状況を再現する
        Tag::creating(function (): void {
            throw new RuntimeException('タグ登録に失敗しました。');
        });

        try {
            // 失敗が呼び出し元まで伝わり、扱っていたデータが例外メッセージに含まれることを確認する
            $this->assertThrows(
                fn () => $service->update($post, ['content' => '更新後の本文', 'tags' => '新規タグ']),
                RuntimeException::class,
                '本文とタグの更新に失敗しました。',
            );
        } finally {
            // 登録したリスナーが後続のテストへ残らないようにする
            Tag::flushEventListeners();
        }

        // 本文が戻り、sync()のDELETEで外れかけた既存タグの紐付けも残っていることを確認する
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'content' => '更新前の本文']);
        $this->assertDatabaseHas('post_tag', ['post_id' => $post->id, 'tag_id' => $existingTag->id]);
        $this->assertDatabaseMissing('tags', ['name' => '新規タグ']);
    }

    /**
     * 投稿削除後にデータが残らないことを確認する
     */
    public function test_delete_removes_post_and_returns_user(): void
    {
        // 削除対象の投稿を用意する
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'user_'.Str::random(10).'@example.com',
            'password' => Hash::make('password'),
        ]);
        $post = Post::create(['user_id' => $user->id, 'content' => '削除対象']);

        $service = new PostService;
        $deleted = $service->delete($post);

        // 削除後にDBから消えていることを確認する
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        $this->assertTrue($deleted->relationLoaded('user'));
        $this->assertSame($user->id, $deleted->user->id);
    }
}
