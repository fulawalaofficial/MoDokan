<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesShop;
use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class DashboardController extends Controller
{
    use ResolvesShop;

    public function index(ReportService $reports): JsonResponse
    {
        try {
            $shopId = $this->shopId();

            return response()->json([
                'success' => true,
                'data' => $reports->dashboard($shopId),
            ]);
        } catch (Throwable $e) {
            Log::error('Dashboard API failed', [
                'user_id' => request()->user()?->id,
                'shop_id' => request()->user()?->shop_id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $payload = [
                'success' => false,
                'message' => 'Unable to load dashboard.',
            ];

            if (config('app.debug')) {
                $payload['error'] = $e->getMessage();
            }

            return response()->json($payload, 500);
        }
    }
}
