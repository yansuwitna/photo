<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = [
        'device_type', 'name', 'identifier', 'status',
        'ip_address', 'metadata', 'last_seen'
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_seen' => 'datetime',
    ];
}