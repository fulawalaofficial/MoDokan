<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Shop;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait ResolvesShop
{
    protected function shop(): Shop
    {
        $user = request()->user();

        if (!$user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        /*
         * IMPORTANT:
         * Do not use "$user->shop" here.
         *
         * If the User model does not have a shop() relationship, accessing
         * $user->shop can throw an exception and the dashboard becomes a 500.
         *
         * We resolve the shop directly from users.shop_id instead.
         */
        $shopId = (int) ($user->getAttribute('shop_id') ?? 0);

        if ($shopId <= 0) {
            throw new HttpException(403, 'Shop not found for this user.');
        }

        $shop = Shop::query()->find($shopId);

        if (!$shop) {
            throw new HttpException(403, 'Assigned shop does not exist.');
        }

        return $shop;
    }

    protected function shopId(): int
    {
        return (int) $this->shop()->getKey();
    }
}
