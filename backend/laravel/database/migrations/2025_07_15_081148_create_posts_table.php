<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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
        Schema::create('posts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->text('content')->nullable();
            $table->string('image_mime')->nullable();
            // テスト時に使用するSQLiteは、LONGBLOBに対応していない都合により、一時的にBLOB型で設定
            $table->binary('image_data')->nullable();
            $table->timestamps();
        });

        // MySQLの場合、LONGBLOBへ拡張（SQLiteはそのまま）
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE posts MODIFY image_data LONGBLOB NULL');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
