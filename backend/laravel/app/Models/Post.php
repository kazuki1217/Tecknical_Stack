<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content',
        'image_data',
        'image_mime',
    ];

    protected $hidden = [
        'image_data',
    ];

    protected $appends = ['image_base64'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getImageBase64Attribute()
    {
        if ($this->image_data && $this->image_mime) {
            return 'data:' . $this->image_mime . ';base64,' . base64_encode($this->image_data);
        }
        return null;
    }
}
