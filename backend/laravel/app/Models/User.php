<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    // タイムスタンプ（created_at/updated_at）を自動で生成しないように設定
    public $timestamps = false;

    // アカウント登録時、入力を受け付けるカラムを指定
    protected $fillable = [
        'name',
        'email',
        'password',
        'created_at'
    ];

    // アカウント登録時、パスワードをハッシュ化して保存するように設定
    protected function casts(): array
    {
        return ['password' => 'hashed',];
    }

    // APIレスポンス時に隠すカラムを設定
    protected $hidden = ['password'];
}
