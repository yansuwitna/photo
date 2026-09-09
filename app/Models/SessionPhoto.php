<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionPhoto extends Model
{
    protected $fillable = [
        'session_id', 'slot_index', 'original_path', 'edited_path',
        'thumbnail_path', 'width', 'height', 'is_accepted',
        'retake_count', 'camera_metadata'
    ];

    protected $casts = [
        'slot_index' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'is_accepted' => 'boolean',
        'retake_count' => 'integer',
        'camera_metadata' => 'array',
    ];

    public function session()
    {
        return $this->belongsTo(BoothSession::class, 'session_id');
    }
}