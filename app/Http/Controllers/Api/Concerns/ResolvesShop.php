<?php
namespace App\Http\Controllers\Api\Concerns;
use App\Models\Shop;

trait ResolvesShop
{
    protected function shop(): Shop
    {
        $user = request()->user();
        abort_unless($user && $user->shop_id, 403, 'Shop not found for this user.');
        return $user->shop;
    }

    protected function shopId(): int
    {
        return $this->shop()->id;
    }
}
