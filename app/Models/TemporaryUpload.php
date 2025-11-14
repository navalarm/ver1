<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemporaryUpload extends Model
{
    protected $fillable = [
        'session_id',
        'image_path',
        'prompt',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
