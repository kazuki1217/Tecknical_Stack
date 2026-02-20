<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = [
        'name',
    ];

    // 投稿テーブルと多対多のリレーションを定義
    public function posts()
    {
        return $this->belongsToMany(Post::class)->withTimestamps();
    }
}
