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
            throw new AuthenticationException(
                'Unauthenticated.'
            );
        }

        $middlewareShop =
            $request->attributes->get('current_shop');

        if ($middlewareShop instanceof Shop) {
            return $middlewareShop;
        }

        $shopId = (int) (
            $user->getAttribute('shop_id') ?? 0
        );

        if ($shopId > 0) {
            $shop = Shop::query()->find($shopId);

            if ($shop) {
                return $shop;
            }
        }

        // Same repair fallback used by EnsureShopActive.
        $ownedShop = Shop::query()
            ->where('owner_id', $user->getKey())
            ->first();

        if ($ownedShop) {
            $user->forceFill([
                'shop_id' => $ownedShop->getKey(),
            ])->saveQuietly();

            return $ownedShop;
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
