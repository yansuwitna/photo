<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Camera extends Model
{
    protected $fillable = [
        'name', 'brand', 'model', 'adapter', 'connection_type',
        'port', 'status', 'battery_level', 'storage_remaining',
        'iso', 'shutter_speed', 'aperture', 'white_balance',
        'focus_mode', 'is_default', 'capabilities', 'error_message'
    ];

    protected $casts = [
        'battery_level' => 'integer',
        'is_default' => 'boolean',
        'capabilities' => 'array',
    ];

    public function sessions()
    {
        return $this->hasMany(BoothSession::class);
    }
}