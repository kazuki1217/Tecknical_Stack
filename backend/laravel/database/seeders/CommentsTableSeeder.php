<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommentsTableSeeder extends Seeder
{
    public function run(): void
    {
        $postIds = DB::table('posts')->pluck('id');
        $userIds = DB::table('users')->pluck('id');

        if ($postIds->isEmpty() || $userIds->isEmpty()) {
            return;
        }

        $now = now();
        $comments = [];
        $commentMap = [
            0 => ['癒される。', 'きれい。'],
            1 => ['落ち着く。', 'いい感じ。'],
            2 => ['あたたかい。', '雰囲気がいい。'],
        ];

        foreach ($postIds as $index => $postId) {
            $userA = $userIds[$index % $userIds->count()];
            $userB = $userIds[($index + 1) % $userIds->count()];
            $commentSet = $commentMap[$index] ?? ['いい。', 'ナイス。'];

            $comments[] = [
                'post_id' => $postId,
                'user_id' => $userA,
                'content' => $commentSet[0],
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $comments[] = [
                'post_id' => $postId,
                'user_id' => $userB,
                'content' => $commentSet[1],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('comments')->insert($comments);
    }
}
