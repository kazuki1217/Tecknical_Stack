<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        DB::statement("
           CREATE TABLE posts (
               id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
               user_id BIGINT UNSIGNED NOT NULL,
               content TEXT NULL,
               created_at TIMESTAMP NULL,
               updated_at TIMESTAMP NULL,
               image_mime VARCHAR(255) NULL,
               image_data LONGBLOB NULL
           )
       ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        DB::statement("DROP TABLE IF EXISTS posts");
    }
};
