<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinalPhoto extends Model
{
    protected $fillable = [
        'session_id', 'file_path', 'thumbnail_path',
        'width', 'height', 'mime_type', 'file_size'
    ];

    protected $casts = [
        'width' => 'integer',
        'height' => 'integer',
        'file_size' => 'integer',
    ];

    public function session()
    {
        return $this->belongsTo(BoothSession::class, 'session_id');
    }
}