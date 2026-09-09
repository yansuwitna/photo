<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'event_date', 'location',
        'logo_path', 'default_price', 'extra_print_price',
        'watermark_text', 'watermark_logo', 'countdown_seconds',
        'is_active', 'custom_settings'
    ];

    protected $casts = [
        'event_date' => 'date',
        'is_active' => 'boolean',
        'default_price' => 'float',
        'extra_print_price' => 'float',
        'custom_settings' => 'array',
    ];

    public function sessions()
    {
        return $this->hasMany(BoothSession::class, 'event_id');
    }
}