<?php

namespace Tests\Feature;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * 大量ダミーデータ投入コマンドの動作を確認するFeatureテスト。
 */
class SeedLargeDataCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 指定した件数の投稿が投入され、5,000件単位のバルクINSERTに分割されることを確認する。
     */
    public function test_it_bulk_inserts_the_requested_posts_in_chunks(): void
    {
        $postInsertQueries = 0;

        DB::listen(function (QueryExecuted $query) use (&$postInsertQueries): void {
            if (str_starts_with($query->sql, 'insert into "posts"')) {
                $postInsertQueries++;
            }
        });

        // 5,000件の境界を越えさせ、投稿INSERTが複数のバルククエリになることも検証する。
        $this->artisan('db:seed-large', ['posts' => '5001'])
            ->assertSuccessful();

        $this->assertDatabaseCount('posts', 5001);
        $this->assertDatabaseCount('users', 1);
        $this->assertSame(2, $postInsertQueries);

        // すべての投稿が、Seederで作成した投稿者を参照していることを確認する。
        $this->assertSame(0, DB::table('posts')
            ->leftJoin('users', 'posts.user_id', '=', 'users.id')
            ->whereNull('users.id')
            ->count());

        $this->assertSame(495, DB::table('posts')->where('content', 'like', '頻出語%')->count());
        $this->assertSame(5, DB::table('posts')->where('content', 'like', '希少語%')->count());
    }

    /**
     * 投稿件数に不正な値を指定した場合、コマンドが失敗し、
     * usersやpostsへ途中データを投入しないことを確認するテスト。
     */
    public function test_it_rejects_an_invalid_post_count_without_inserting_data(): void
    {
        $this->artisan('db:seed-large', ['posts' => '100abc'])
            ->expectsOutput('投稿件数には1以上の整数を指定してください。')
            ->assertFailed();

        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseCount('users', 0);
    }

    /**
     * 投稿投入の途中で失敗した場合、専用ユーザーのCASCADE削除により投入済み投稿も削除することを確認する。
     */
    public function test_it_removes_inserted_posts_when_a_later_chunk_fails(): void
    {
        $postInsertQueries = 0;

        DB::listen(function (QueryExecuted $query) use (&$postInsertQueries): void {
            if (! str_starts_with($query->sql, 'insert into "posts"')) {
                return;
            }

            $postInsertQueries++;

            // 1チャンク目の確定後に失敗させ、完了済み投稿も後片付けされることを検証する。
            if ($postInsertQueries === 2) {
                throw new RuntimeException('テスト用の投稿投入エラー');
            }
        });

        $this->artisan('db:seed-large', ['posts' => '5001'])
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('posts', 0);
    }
}
