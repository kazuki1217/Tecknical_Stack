<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PostsTableSeeder extends Seeder
{
    public function run(): void
    {
        $images = [
            1 => 'sample1.jpg',
            2 => 'sample2.jpg',
            3 => 'sample3.jpg',
        ];

        $data = [];

        foreach ($images as $userId => $fileName) {
            $imagePath = storage_path('app/public/' . $fileName);

            if (file_exists($imagePath)) {
                $imageData = file_get_contents($imagePath);
                $mimeType = mime_content_type($imagePath);
            } else {
                $imageData = null;
                $mimeType = null;
            }

            $data[] = [
                'user_id'    => $userId,
                'content'    => "サンプル投稿{$userId}の内容です。",
                'created_at' => now(),
                'updated_at' => now(),
                'image_mime' => $mimeType,
                'image_data' => $imageData,
            ];
        }

        DB::table('posts')->insert($data);
    }
}
