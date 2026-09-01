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
                'code' => 'UNAUTHENTICATED',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Super Admin
        |--------------------------------------------------------------------------
        |
        | Super admins are not tied to a normal shop account.
        | The method check keeps this middleware compatible with older User models.
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
            | Resolve shop from users.shop_id
            |--------------------------------------------------------------------------
            */
            $shopId = (int) ($user->getAttribute('shop_id') ?? 0);

            $shop = $shopId > 0
                ? Shop::query()->find($shopId)
                : null;

            /*
            |--------------------------------------------------------------------------
            | Compatibility / self-repair
            |--------------------------------------------------------------------------
            |
            | Older registrations can have shops.owner_id populated while
            | users.shop_id is NULL or stale.
            |
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
                    'user_id' => $user->getKey(),
                ], 403);
            }

            /*
            |--------------------------------------------------------------------------
            | Shop status
            |--------------------------------------------------------------------------
            |
            | Active / active / ACTIVE and Approved / approved all work.
            |
            */
            $rawStatus = trim((string) ($shop->status ?? ''));
            $status = strtolower($rawStatus);

            if (!in_array($status, ['active', 'approved'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => $status === 'pending'
                        ? 'Your shop is waiting for admin approval.'
                        : 'Your shop is inactive.',
                    'shop_status' => $rawStatus,
                    'shop_id' => $shop->getKey(),
                    'code' => $status === 'pending'
                        ? 'SHOP_PENDING'
                        : 'SHOP_INACTIVE',
                ], 403);
            }

            /*
            |--------------------------------------------------------------------------
            | Share the resolved shop
            |--------------------------------------------------------------------------
            |
            | Controllers and ResolvesShop can now use the same resolved shop.
            |
            */
            $request->attributes->set('current_shop', $shop);
            $request->attributes->set('current_shop_id', (int) $shop->getKey());

            // Prevent unnecessary additional relationship queries.
            $user->setRelation('shop', $shop);

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
