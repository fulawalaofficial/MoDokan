<?php

namespace App\Http\Middleware;

use App\Models\Shop;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EnsureShopActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        try {
            $shopId = (int) ($user->shop_id ?? 0);

            // Normal case: user already has shop_id.
            $shop = $shopId > 0
                ? Shop::query()->find($shopId)
                : null;

            /*
             * Compatibility/self-repair for older registered owner accounts:
             * if users.shop_id is NULL but shops.owner_id points to this user,
             * recover the shop and save the missing shop_id.
             */
            if (!$shop) {
                $shop = Shop::query()
                    ->where('owner_id', $user->id)
                    ->first();

                if ($shop && (int) ($user->shop_id ?? 0) !== (int) $shop->id) {
                    $user->forceFill([
                        'shop_id' => $shop->id,
                    ])->saveQuietly();
                }
            }

            if (!$shop) {
                return response()->json([
                    'message' => 'Shop not found for this user.',
                    'code' => 'SHOP_NOT_FOUND',
                ], 403);
            }

            $userStatus = strtolower(trim((string) ($user->status ?? 'active')));
            if ($userStatus !== '' && !in_array($userStatus, ['active', 'approved'], true)) {
                return response()->json([
                    'message' => 'Your user account is not active.',
                    'code' => 'USER_INACTIVE',
                ], 403);
            }

            $shopStatus = strtolower(trim((string) ($shop->status ?? 'active')));
            if ($shopStatus !== '' && !in_array($shopStatus, ['active', 'approved'], true)) {
                return response()->json([
                    'message' => 'Your shop account is not active.',
                    'code' => 'SHOP_INACTIVE',
                    'shop_status' => $shop->status,
                ], 403);
            }

            // Reuse the resolved shop in controllers/traits if required.
            $request->attributes->set('current_shop', $shop);

            return $next($request);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Unable to validate shop account.',
                'code' => 'SHOP_MIDDLEWARE_ERROR',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
