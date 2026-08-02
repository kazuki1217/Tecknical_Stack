<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * posts.contentに日本語検索用のFULLTEXTインデックスを追加する。
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // 日本語のように空白で単語を区切らない本文を検索するため、MySQL組み込みのngramパーサーを使用する。
        DB::statement(
            'ALTER TABLE posts ADD FULLTEXT INDEX posts_content_fulltext (content) WITH PARSER ngram'
        );
    }

    /**
     * posts.contentのFULLTEXTインデックスを削除する。
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE posts DROP INDEX posts_content_fulltext');
    }
};
