<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Generation extends Model
{
    protected $fillable = [
        'user_id',
        'hash',
        'prompt',
        'input_image_path',
        'output_video_path',
        'status',
        'runware_task_id',
        'error_message',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($generation) {
            if (empty($generation->hash)) {
                $generation->hash = bin2hex(random_bytes(16));
            }
        });
    }
}
