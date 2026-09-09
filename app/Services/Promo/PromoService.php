<?php

namespace App\Services\Promo;

use App\Models\Promo;

class PromoService
{
    public function validatePromo(string $code, float $totalAmount): array
    {
        $promo = Promo::where('code', strtoupper($code))->first();

        if (!$promo) {
            return ['valid' => false, 'message' => 'Kode promo tidak ditemukan.'];
        }

        if (!$promo->is_active) {
            return ['valid' => false, 'message' => 'Kode promo ini sudah nonaktif.'];
        }

        if ($promo->start_date && now()->lt($promo->start_date)) {
            return ['valid' => false, 'message' => 'Kode promo belum dapat digunakan.'];
        }

        if ($promo->end_date && now()->gt($promo->end_date)) {
            return ['valid' => false, 'message' => 'Kode promo sudah kedaluwarsa.'];
        }

        if ($promo->usage_limit && $promo->usage_count >= $promo->usage_limit) {
            return ['valid' => false, 'message' => 'Batas pemakaian promo sudah terpenuhi.'];
        }

        if ($promo->min_spend && $totalAmount < $promo->min_spend) {
            return ['valid' => false, 'message' => "Minimal transaksi Rp " . number_format($promo->min_spend, 0, ',', '.') . " untuk promo ini."];
        }

        $discount = 0.0;
        if ($promo->discount_type === 'percentage') {
            $discount = ($totalAmount * ($promo->discount_value / 100));
            if ($promo->max_discount && $discount > $promo->max_discount) {
                $discount = $promo->max_discount;
            }
        } else {
            $discount = min($totalAmount, (float)$promo->discount_value);
        }

        return [
            'valid' => true,
            'promo' => $promo,
            'discount' => $discount,
            'message' => 'Promo berhasil diterapkan.',
        ];
    }
}