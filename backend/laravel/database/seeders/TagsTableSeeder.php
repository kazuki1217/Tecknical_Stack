<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TagsTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $tagNames = ['海', 'クラゲ', '植物', '葉', '焚火', '夜'];

        $tags = [];
        foreach ($tagNames as $name) {
            $tags[] = [
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('tags')->insert($tags);

        $tagIdByName = DB::table('tags')->pluck('id', 'name');
        $postIds = DB::table('posts')->pluck('id');

        $postTagMap = [
            0 => ['海', 'クラゲ'],
            1 => ['植物', '葉'],
            2 => ['焚火', '夜'],
        ];

        $postTagRows = [];
        foreach ($postIds as $index => $postId) {
            $tagSet = $postTagMap[$index] ?? ['植物'];

            foreach ($tagSet as $tagName) {
                if (! isset($tagIdByName[$tagName])) {
                    continue;
                }

                $postTagRows[] = [
                    'post_id' => $postId,
                    'tag_id' => $tagIdByName[$tagName],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (! empty($postTagRows)) {
            DB::table('post_tag')->insert($postTagRows);
        }
    }
}
