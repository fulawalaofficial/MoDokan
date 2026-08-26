<?php
namespace App\Services;

use App\Models\Product;
use App\Models\StockHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function move(Product $product, float $quantity, string $type, ?int $userId = null, ?string $note = null, ?string $referenceType = null, ?int $referenceId = null): Product
    {
        return DB::transaction(function () use ($product, $quantity, $type, $userId, $note, $referenceType, $referenceId) {
            $before = (float) $product->quantity;
            $after = match ($type) {
                'in', 'return' => $before + $quantity,
                'out', 'sale', 'damaged' => $before - $quantity,
                default => throw ValidationException::withMessages(['type' => 'Invalid stock movement type.']),
            };

            if ($after < 0) {
                throw ValidationException::withMessages(['quantity' => 'Insufficient stock for '.$product->name]);
            }

            $product->update(['quantity' => $after]);

            StockHistory::create([
                'shop_id' => $product->shop_id,
                'product_id' => $product->id,
                'user_id' => $userId,
                'type' => $type,
                'quantity' => $quantity,
                'before_quantity' => $before,
                'after_quantity' => $after,
                'note' => $note,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);

            return $product->fresh();
        });
    }
}
