<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoUsage extends Model
{
    protected $fillable = [
        'promo_id', 'session_id', 'discount_applied', 'used_at'
    ];

    protected $casts = [
        'discount_applied' => 'float',
        'used_at' => 'datetime',
    ];

    public function promo()
    {
        return $this->belongsTo(Promo::class);
    }

    public function session()
    {
        return $this->belongsTo(BoothSession::class, 'session_id');
    }
}