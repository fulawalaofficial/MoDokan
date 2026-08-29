<?php

namespace App\Http\Middleware;

use App\Models\Shop;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ShopActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Super admin
        |--------------------------------------------------------------------------
        |
        | Some User models have isSuperAdmin(); some do not.
        | Never call the method unless it exists.
        |
        */
        if (
            method_exists($user, 'isSuperAdmin') &&
            $user->isSuperAdmin()
        ) {
            return $next($request);
        }

        try {
            /*
            |--------------------------------------------------------------------------
            | Resolve shop safely
            |--------------------------------------------------------------------------
            |
            | Do not depend on $user->shop being available.
            | First resolve from users.shop_id.
            |
            */
            $shopId = (int) ($user->getAttribute('shop_id') ?? 0);

            $shop = $shopId > 0
                ? Shop::query()->find($shopId)
                : null;

            /*
             * Compatibility/self-repair:
             * Older registrations may have shops.owner_id set correctly
             * while users.shop_id is NULL.
             */
            if (!$shop) {
                $shop = Shop::query()
                    ->where('owner_id', $user->getKey())
                    ->first();

                if ($shop) {
                    $user->forceFill([
                        'shop_id' => $shop->getKey(),
                    ])->saveQuietly();
                }
            }

            if (!$shop) {
                return response()->json([
                    'success' => false,
                    'message' => 'No shop is linked with this user.',
                    'code' => 'SHOP_NOT_LINKED',
                ], 403);
            }

            /*
            |--------------------------------------------------------------------------
            | Shop status
            |--------------------------------------------------------------------------
            |
            | Status checks are case-insensitive so Active/active both work.
            |
            */
            $status = strtolower(
                trim((string) ($shop->status ?? 'active'))
            );

            if (!in_array($status, ['active', 'approved'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => $status === 'pending'
                        ? 'Your shop is waiting for admin approval.'
                        : 'Your shop is inactive.',
                    'shop_status' => $shop->status,
                    'code' => $status === 'pending'
                        ? 'SHOP_PENDING'
                        : 'SHOP_INACTIVE',
                ], 403);
            }

            /*
             * Make the resolved shop available to controllers/traits
             * without another database query.
             */
            $request->attributes->set('current_shop', $shop);

            return $next($request);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to validate shop account.',
                'code' => 'SHOP_ACTIVE_MIDDLEWARE_ERROR',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }
}
