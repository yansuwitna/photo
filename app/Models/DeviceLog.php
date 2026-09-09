<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'device_type', 'device_id', 'event', 'message', 'severity', 'payload', 'created_at'
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];
}