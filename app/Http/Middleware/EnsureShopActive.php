<?php

namespace App\Http\Middleware;

use App\Models\Shop;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EnsureShopActive
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'code' => 'AUTH_REQUIRED',
            ], 401);
        }

        try {
            $shop = $this->resolveShop($user);

            if (!$shop) {
                return response()->json([
                    'message' =>
                        'No shop is linked to this account.',
                    'code' => 'SHOP_NOT_LINKED',
                    'user_id' => $user->getKey(),
                ], 403);
            }

            $userStatus = strtolower(
                trim((string) ($user->status ?? 'active'))
            );

            if (
                $userStatus !== '' &&
                !in_array(
                    $userStatus,
                    ['active', 'approved'],
                    true
                )
            ) {
                return response()->json([
                    'message' =>
                        'Your user account is not active.',
                    'code' => 'USER_INACTIVE',
                ], 403);
            }

            $shopStatus = strtolower(
                trim((string) ($shop->status ?? 'active'))
            );

            if (
                $shopStatus !== '' &&
                !in_array(
                    $shopStatus,
                    ['active', 'approved'],
                    true
                )
            ) {
                return response()->json([
                    'message' =>
                        'Your shop account is not active.',
                    'code' => 'SHOP_INACTIVE',
                    'shop_status' => $shop->status,
                ], 403);
            }

            // Avoid re-querying the shop in controllers.
            $request->attributes->set(
                'current_shop',
                $shop
            );

            return $next($request);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' =>
                    'Unable to resolve the shop account.',
                'code' => 'SHOP_RESOLUTION_FAILED',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }

    private function resolveShop($user): ?Shop
    {
        $shopId = (int) (
            $user->getAttribute('shop_id') ?? 0
        );

        if ($shopId > 0) {
            $shop = Shop::query()->find($shopId);

            if ($shop) {
                return $shop;
            }
        }

        /*
         * SELF-HEAL FOR OLD REGISTRATIONS:
         *
         * If an older User model did not have "shop_id" in $fillable,
         * User::create([... 'shop_id' => ...]) could leave shop_id NULL.
         * The shops table also stores owner_id, so recover the mapping.
         */
        $ownedShop = Shop::query()
            ->where('owner_id', $user->getKey())
            ->first();

        if (!$ownedShop) {
            return null;
        }

        if (
            method_exists($user, 'getTable') &&
            $user->getAttribute('shop_id') != $ownedShop->getKey()
        ) {
            $user->forceFill([
                'shop_id' => $ownedShop->getKey(),
            ])->saveQuietly();
        }

        return $ownedShop;
    }
}
