<?php
namespace App\Services;
use App\Models\Sale;
use App\Models\Repair;
use App\Models\Payment;

class InvoiceNumberService
{
    public function saleNo(int $shopId, string $prefix = 'INV'): string
    {
        $next = Sale::where('shop_id', $shopId)->count() + 1;
        return $prefix . '-' . date('Ymd') . '-' . str_pad((string)$next, 5, '0', STR_PAD_LEFT);
    }

    public function repairNo(int $shopId): string
    {
        $next = Repair::where('shop_id', $shopId)->count() + 1;
        return 'REP-' . date('Ymd') . '-' . str_pad((string)$next, 5, '0', STR_PAD_LEFT);
    }

    public function paymentNo(int $shopId): string
    {
        $next = Payment::where('shop_id', $shopId)->count() + 1;
        return 'PAY-' . date('Ymd') . '-' . str_pad((string)$next, 5, '0', STR_PAD_LEFT);
    }
}
