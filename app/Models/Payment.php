<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'session_id', 'method', 'subtotal', 'discount_amount',
        'tax_amount', 'total_amount', 'amount_paid', 'change_amount',
        'status', 'reference_number', 'qris_payload', 'paid_at', 'notes'
    ];

    protected $casts = [
        'subtotal' => 'float',
        'discount_amount' => 'float',
        'tax_amount' => 'float',
        'total_amount' => 'float',
        'amount_paid' => 'float',
        'change_amount' => 'float',
        'paid_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(BoothSession::class, 'session_id');
    }
}