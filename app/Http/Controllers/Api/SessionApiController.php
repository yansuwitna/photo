<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BoothSession;
use App\Services\Session\SessionManager;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionApiController extends Controller
{
    protected SessionManager $sessionManager;
    protected PaymentService $paymentService;

    public function __construct(SessionManager $sessionManager, PaymentService $paymentService)
    {
        $this->sessionManager = $sessionManager;
        $this->paymentService = $paymentService;
    }

    public function start(Request $request): JsonResponse
    {
        $templateId = $request->input('template_id');
        $eventId = $request->input('event_id');

        $session = $this->sessionManager->startNewSession($templateId, $eventId);

        return response()->json([
            'success' => true,
            'session' => $session->load(['event', 'template']),
        ]);
    }

    public function selectTemplate(Request $request, string $sessionId): JsonResponse
    {
        $session = BoothSession::findOrFail($sessionId);
        $templateId = $request->input('template_id');

        $session = $this->sessionManager->selectTemplate($session, $templateId);

        return response()->json([
            'success' => true,
            'session' => $session->load(['event', 'template']),
        ]);
    }

    public function setFrame(Request $request, string $sessionId): JsonResponse
    {
        $session = BoothSession::findOrFail($sessionId);
        $framePath = $request->input('frame_path');
        $bgColor = $request->input('background_color');

        $metadata = $session->metadata ?? [];
        if ($request->has('frame_path')) {
            if ($framePath) {
                $metadata['custom_overlay_image'] = $framePath;
            } else {
                unset($metadata['custom_overlay_image']);
            }
        }

        if ($request->has('background_color')) {
            if ($bgColor) {
                $metadata['custom_background_color'] = $bgColor;
            } else {
                unset($metadata['custom_background_color']);
            }
        }

        $session->update(['metadata' => $metadata]);

        return response()->json([
            'success' => true,
            'message' => 'Kustomisasi berhasil disimpan.',
            'session' => $session->fresh()->load(['event', 'template', 'photos', 'finalPhotos']),
        ]);
    }

    public function capture(Request $request, string $sessionId): JsonResponse
    {
        $session = BoothSession::with(['event', 'template', 'photos'])->findOrFail($sessionId);
        $slotIndex = $request->input('slot_index');
        $imageData = $request->input('image_data') ?? $request->file('image_file');

        $result = $this->sessionManager->captureSlot($session, $slotIndex, $imageData);

        return response()->json($result);
    }

    public function retake(Request $request, string $sessionId): JsonResponse
    {
        $session = BoothSession::with(['event', 'template', 'photos'])->findOrFail($sessionId);
        $slotIndex = $request->input('slot_index', 1);
        $imageData = $request->input('image_data') ?? $request->file('image_file');

        $result = $this->sessionManager->retakePhoto($session, $slotIndex, $imageData);

        return response()->json($result);
    }

    public function compose(string $sessionId): JsonResponse
    {
        $session = BoothSession::with(['event', 'template', 'photos'])->findOrFail($sessionId);

        $result = $this->sessionManager->composeTemplate($session);

        $result['session'] = $session->fresh()->load(['event', 'template', 'photos', 'finalPhotos']);
        return response()->json($result);
    }

    public function print(Request $request, string $sessionId): JsonResponse
    {
        $session = BoothSession::with(['event', 'template'])->findOrFail($sessionId);
        $copies = $request->input('copies', 1);

        $result = $this->sessionManager->printFinalPhoto($session, $copies);

        return response()->json($result);
    }

    public function payment(Request $request, string $sessionId): JsonResponse
    {
        $session = BoothSession::with(['event'])->findOrFail($sessionId);

        $method = $request->input('method', 'cash');
        $amountPaid = (float)$request->input('amount_paid', 0);
        $promoCode = $request->input('promo_code');
        $copies = (int)$request->input('copies', 1);
        $additionalPrints = max(0, $copies - 1);

        $payment = $this->paymentService->processPayment($session, $method, $amountPaid, $promoCode, $additionalPrints);

        return response()->json([
            'success' => true,
            'payment' => $payment,
            'session' => $session->fresh(),
        ]);
    }

    public function status(string $sessionId): JsonResponse
    {
        $session = BoothSession::with(['event', 'template', 'photos', 'finalPhotos', 'payment', 'printJobs'])->findOrFail($sessionId);

        return response()->json([
            'success' => true,
            'session' => $session,
        ]);
    }
}