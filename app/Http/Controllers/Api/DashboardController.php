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

    public function index(
        ReportService $reports
    ): JsonResponse {
        try {
            $shop = $this->shop();

            $data = $reports->dashboard(
                (int) $shop->getKey()
            );

            /*
             * Keep the response FLAT because the current React Native
             * DashboardScreen reads response.data directly.
             */
            $data['shop'] = [
                'id' => $shop->id,
                'name' => $shop->name,
                'status' => $shop->status,
                'currency' => $shop->currency,
                'invoice_prefix' =>
                    $shop->invoice_prefix,
            ];

            return response()
                ->json($data)
                ->header(
                    'X-MoDokana-Dashboard-Patch',
                    '20260829-v2'
                );
        } catch (Throwable $e) {
            Log::error(
                'MoDokana dashboard controller failed',
                [
                    'user_id' =>
                        request()->user()?->id,
                    'shop_id' =>
                        request()->user()?->shop_id,
                    'message' =>
                        $e->getMessage(),
                    'file' =>
                        $e->getFile(),
                    'line' =>
                        $e->getLine(),
                ]
            );

            return response()
                ->json([
                    'message' =>
                        'Dashboard could not be loaded.',
                    'code' =>
                        'DASHBOARD_CONTROLLER_FAILED',
                    'error' =>
                        config('app.debug')
                            ? $e->getMessage()
                            : null,
                ], 500)
                ->header(
                    'X-MoDokana-Dashboard-Patch',
                    '20260829-v2'
                );
        }
    }
}
