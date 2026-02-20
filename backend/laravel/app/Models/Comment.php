<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = [
        'post_id',
        'user_id',
        'content',
    ];

    // 投稿テーブルと多対一のリレーションを定義
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    // ユーザーテーブルと多対一のリレーションを定義
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
