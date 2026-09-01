<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesShop;
use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class DashboardController extends Controller
{
    use ResolvesShop;

    public function index(
        Request $request,
        ReportService $reports
    ): JsonResponse {
        /*
        |--------------------------------------------------------------------------
        | Resolve the current shop
        |--------------------------------------------------------------------------
        |
        | ShopActive middleware already resolves the active shop and stores it in
        | the request as "current_shop". Prefer that value here so the dashboard
        | does not perform a second shop lookup unnecessarily.
        |
        | We keep ResolvesShop as a fallback for compatibility with older code.
        |
        */
        try {
            $shop = $request->attributes->get('current_shop');

            if (!$shop) {
                $shop = $this->shop();
            }
        } catch (HttpExceptionInterface $e) {
            Log::warning('MoDokana dashboard shop access failed', [
                'user_id' => $request->user()?->id,
                'user_shop_id' => $request->user()?->shop_id,
                'status' => $e->getStatusCode(),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Shop access is unavailable.',
                'code' => 'DASHBOARD_SHOP_ACCESS_FAILED',
            ], $e->getStatusCode());
        } catch (Throwable $e) {
            Log::error('MoDokana dashboard shop resolution failed', [
                'user_id' => $request->user()?->id,
                'user_shop_id' => $request->user()?->shop_id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to resolve the current shop.',
                'code' => 'DASHBOARD_SHOP_RESOLUTION_FAILED',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

        if (!$shop || !(int) $shop->getKey()) {
            return response()->json([
                'success' => false,
                'message' => 'No active shop is linked to this account.',
                'code' => 'DASHBOARD_SHOP_NOT_FOUND',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Build dashboard
        |--------------------------------------------------------------------------
        */
        try {
            $data = $reports->dashboard((int) $shop->getKey());

            /*
             * Keep the response FLAT because the current React Native
             * DashboardScreen reads response.data directly.
             */
            $data['shop'] = [
                'id' => (int) $shop->getKey(),
                'name' => (string) ($shop->name ?? ''),
                'status' => $shop->status ?? null,
                'currency' => $shop->currency ?? 'INR',
                'invoice_prefix' => $shop->invoice_prefix ?? null,
            ];

            $data['success'] = true;

            return response()
                ->json($data)
                ->header(
                    'X-MoDokana-Dashboard-Patch',
                    '20260901-v3'
                );
        } catch (Throwable $e) {
            Log::error('MoDokana dashboard controller failed', [
                'user_id' => $request->user()?->id,
                'user_shop_id' => $request->user()?->shop_id,
                'resolved_shop_id' => $shop?->getKey(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => config('app.debug')
                    ? $e->getTraceAsString()
                    : null,
            ]);

            return response()
                ->json([
                    'success' => false,
                    'message' => 'Dashboard could not be loaded.',
                    'code' => 'DASHBOARD_CONTROLLER_FAILED',
                    'error' => config('app.debug')
                        ? $e->getMessage()
                        : null,
                ], 500)
                ->header(
                    'X-MoDokana-Dashboard-Patch',
                    '20260901-v3'
                );
        }
    }
}
