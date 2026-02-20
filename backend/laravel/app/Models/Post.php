<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    // 新規投稿の登録時、入力を受け付けるカラムを指定
    protected $fillable = [
        'user_id',
        'content',
        'image_data',
        'image_mime',
    ];

    // APIレスポンス時に隠すカラムを設定
    protected $hidden = [
        'image_data',
    ];

    // APIレスポンス時に追加する項目
    protected $appends = ['image_base64'];

    // 画像ファイルに関連する情報が存在する場合、<img src="..."> で表示できる形式に変換
    public function getImageBase64Attribute()
    {
        if ($this->image_data && $this->image_mime) {
            return 'data:' . $this->image_mime . ';base64,' . base64_encode($this->image_data);
        }
        return null;
    }

    // ユーザーテーブルと多対一のリレーションを定義
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // コメントテーブルと一対多のリレーションを定義
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    // タグテーブルと多対多のリレーションを定義
    public function tags()
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }
}
