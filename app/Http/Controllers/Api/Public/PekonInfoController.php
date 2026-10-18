<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Services\WahaService;
use Illuminate\Http\JsonResponse;

class PekonInfoController extends Controller
{
    public function __construct(
        protected WahaService $wahaService
    ) {}

    /**
     * Get dynamic Pekon WhatsApp Center info from active WAHA session or calibrated fallback.
     */
    public function whatsappCenter(): JsonResponse
    {
        $info = $this->wahaService->getWhatsAppCenterInfo();

        return response()->json([
            'status' => 'success',
            'data' => $info,
        ]);
    }
}
