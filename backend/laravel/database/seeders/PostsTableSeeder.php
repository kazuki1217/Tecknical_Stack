<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PostsTableSeeder extends Seeder
{
    public function run(): void
    {
        $images = [
            1 => ['file' => 'sample1.jpg', 'content' => 'クラゲですー'],
            2 => ['file' => 'sample2.jpg', 'content' => '植物ですー'],
            3 => ['file' => 'sample3.jpg', 'content' => '焚火ですー'],
        ];

        $data = [];

        foreach ($images as $userId => $post) {
            $imagePath = storage_path('app/public/' . $post['file']);

            if (file_exists($imagePath)) {
                $imageData = file_get_contents($imagePath);
                $mimeType = mime_content_type($imagePath);
            } else {
                $imageData = null;
                $mimeType = null;
            }

            $data[] = [
                'user_id'    => $userId,
                'content'    => $post['content'],
                'image_mime' => $mimeType,
                'image_data' => $imageData,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('posts')->insert($data);
    }
}
