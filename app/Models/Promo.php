<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    protected $fillable = [
        'code', 'name', 'description', 'discount_type',
        'discount_value', 'min_spend', 'max_discount',
        'usage_limit', 'usage_count', 'start_date', 'end_date', 'is_active'
    ];

    protected $casts = [
        'discount_value' => 'float',
        'min_spend' => 'float',
        'max_discount' => 'float',
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function usages()
    {
        return $this->hasMany(PromoUsage::class);
    }
}