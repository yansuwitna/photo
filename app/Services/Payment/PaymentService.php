<?php

namespace App\Services\Payment;

use App\Models\BoothSession;
use App\Models\Payment;
use App\Models\Promo;
use App\Models\PromoUsage;

class PaymentService
{
    public function calculateTotal(BoothSession $session, ?string $promoCode = null, int $additionalPrints = 0): array
    {
        $basePrice = $session->event ? (float)$session->event->default_price : 25000.0;
        $printPrice = $session->event ? (float)$session->event->extra_print_price : 10000.0;
        $subtotal = $basePrice + ($additionalPrints * $printPrice);

        $discount = 0.0;
        $promo = null;

        if ($promoCode) {
            $promo = Promo::where('code', strtoupper($promoCode))->where('is_active', true)->first();
            if ($promo && ($promo->min_spend <= $subtotal)) {
                if ($promo->discount_type === 'percentage') {
                    $discount = ($subtotal * ($promo->discount_value / 100));
                    if ($promo->max_discount && $discount > $promo->max_discount) {
                        $discount = $promo->max_discount;
                    }
                } else {
                    $discount = min($subtotal, (float)$promo->discount_value);
                }
            }
        }

        $total = max(0, $subtotal - $discount);

        return [
            'base_price' => $basePrice,
            'additional_prints' => $additionalPrints,
            'print_price' => $printPrice,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'promo' => $promo,
        ];
    }

    public function processPayment(BoothSession $session, string $method, float $amountPaid, ?string $promoCode = null, int $additionalPrints = 0): Payment
    {
        $calc = $this->calculateTotal($session, $promoCode, $additionalPrints);
        $total = $calc['total'];

        $status = ($method === 'free' || $amountPaid >= $total) ? 'paid' : 'pending';
        $change = max(0, $amountPaid - $total);

        $payment = Payment::create([
            'session_id' => $session->id,
            'method' => $method,
            'subtotal' => $calc['subtotal'],
            'discount_amount' => $calc['discount'],
            'tax_amount' => 0,
            'total_amount' => $total,
            'amount_paid' => $amountPaid,
            'change_amount' => $change,
            'status' => $status,
            'reference_number' => 'TRX-' . strtoupper(uniqid()),
            'paid_at' => $status === 'paid' ? now() : null,
        ]);

        if ($status === 'paid') {
            $session->update(['payment_status' => 'paid']);
            if ($calc['promo']) {
                PromoUsage::create([
                    'promo_id' => $calc['promo']->id,
                    'session_id' => $session->id,
                    'discount_applied' => $calc['discount'],
                    'used_at' => now(),
                ]);
                $calc['promo']->increment('usage_count');
            }
        }

        return $payment;
    }
}