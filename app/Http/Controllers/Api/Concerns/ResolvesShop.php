<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Shop;
use Illuminate\Http\Request;

trait ResolvesShop
{
    protected function shopId(?Request $request = null): int
    {
        $request = $request ?: request();

        /*
        |--------------------------------------------------------------------------
        | 1. Prefer ShopActive middleware result
        |--------------------------------------------------------------------------
        */
        $attributeShopId = (int) (
            $request->attributes->get('current_shop_id') ?? 0
        );

        if ($attributeShopId > 0) {
            return $attributeShopId;
        }

        $currentShop = $request->attributes->get('current_shop');

        if ($currentShop instanceof Shop) {
            return (int) $currentShop->getKey();
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Resolve from authenticated user
        |--------------------------------------------------------------------------
        */
        $user = $request->user();

        abort_unless(
            $user,
            401,
            'Unauthenticated.'
        );

        if (
            method_exists($user, 'isSuperAdmin') &&
            $user->isSuperAdmin()
        ) {
            $requestedShopId = (int) (
                $request->input('shop_id')
                ?: $request->query('shop_id')
                ?: 0
            );

            abort_unless(
                $requestedShopId > 0 &&
                Shop::query()->whereKey($requestedShopId)->exists(),
                422,
                'A valid shop_id is required for super admin requests.'
            );

            return $requestedShopId;
        }

        $shopId = (int) (
            $user->getAttribute('shop_id') ?? 0
        );

        if ($shopId > 0) {
            $exists = Shop::query()
                ->whereKey($shopId)
                ->exists();

            if ($exists) {
                return $shopId;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Compatibility fallback using shops.owner_id
        |--------------------------------------------------------------------------
        */
        $shop = Shop::query()
            ->where('owner_id', $user->getKey())
            ->first();

        abort_unless(
            $shop,
            403,
            'No shop is linked with this user.'
        );

        if (
            (int) ($user->getAttribute('shop_id') ?? 0) !==
            (int) $shop->getKey()
        ) {
            $user->forceFill([
                'shop_id' => $shop->getKey(),
            ])->saveQuietly();
        }

        return (int) $shop->getKey();
    }

    protected function currentShop(?Request $request = null): Shop
    {
        $request = $request ?: request();

        $currentShop = $request->attributes->get('current_shop');

        if ($currentShop instanceof Shop) {
            return $currentShop;
        }

        $shopId = $this->shopId($request);

        $shop = Shop::query()->find($shopId);

        abort_unless(
            $shop,
            403,
            'Shop not found.'
        );

        return $shop;
    }
}
