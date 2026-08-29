<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Shop;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait ResolvesShop
{
    protected function shop(): Shop
    {
        $request = request();
        $user = $request->user();

        if (!$user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        /*
         * ShopActive middleware already resolved the current shop.
         */
        $resolvedShop = $request->attributes->get('current_shop');

        if ($resolvedShop instanceof Shop) {
            return $resolvedShop;
        }

        /*
         * Fallback: resolve directly from users.shop_id.
         */
        $shopId = (int) ($user->getAttribute('shop_id') ?? 0);

        if ($shopId > 0) {
            $shop = Shop::query()->find($shopId);

            if ($shop) {
                return $shop;
            }
        }

        /*
         * Compatibility fallback for older owner accounts.
         */
        $shop = Shop::query()
            ->where('owner_id', $user->getKey())
            ->first();

        if ($shop) {
            $user->forceFill([
                'shop_id' => $shop->getKey(),
            ])->saveQuietly();

            return $shop;
        }

        throw new HttpException(
            403,
            'Shop not found for this user.'
        );
    }

    protected function shopId(): int
    {
        return (int) $this->shop()->getKey();
    }
}
