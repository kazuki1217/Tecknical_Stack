<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PostsTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $images = [
            1 => ['file' => 'sample1.jpg', 'content' => 'クラゲですー'],
            2 => ['file' => 'sample2.jpg', 'content' => '植物ですー'],
            3 => ['file' => 'sample3.jpg', 'content' => '焚火ですー'],
        ];

        $data = [];

        foreach ($images as $userId => $post) {
            $imagePath = storage_path('app/public/'.$post['file']);

            if (file_exists($imagePath)) {
                $imageData = file_get_contents($imagePath);
                $mimeType = mime_content_type($imagePath);
            } else {
                $imageData = null;
                $mimeType = null;
            }

            $data[] = [
                'user_id' => $userId,
                'content' => $post['content'],
                'image_mime' => $mimeType,
                'image_data' => $imageData,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        for ($i = 1; $i <= 100; $i++) {
            // ページネーション確認用に、画像なしの軽い投稿データを十分な件数用意する
            $data[] = [
                'user_id' => (($i - 1) % 3) + 1,
                'content' => "投稿内容 {$i}",
                'image_mime' => null,
                'image_data' => null,
                'created_at' => $now->copy()->subMinutes($i),
                'updated_at' => $now->copy()->subMinutes($i),
            ];
        }

        DB::table('posts')->insert($data);
    }
}
