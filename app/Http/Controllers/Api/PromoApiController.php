<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Promo\PromoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromoApiController extends Controller
{
    protected PromoService $promoService;

    public function __construct(PromoService $promoService)
    {
        $this->promoService = $promoService;
    }

    public function check(Request $request): JsonResponse
    {
        $code = $request->input('code', '');
        $total = (float)$request->input('total', 0);

        $result = $this->promoService->validatePromo($code, $total);

        return response()->json($result);
    }
}