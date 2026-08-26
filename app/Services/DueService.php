<?php
namespace App\Services;

use App\Models\Customer;
use App\Models\DuePayment;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Repair;
use Illuminate\Support\Facades\DB;

class DueService
{
    public function __construct(protected InvoiceNumberService $numberService) {}

    public function collect(array $data, int $shopId, int $userId): Payment
    {
        return DB::transaction(function () use ($data, $shopId, $userId) {
            $customer = Customer::where('shop_id', $shopId)->findOrFail($data['customer_id']);
            $amount = (float) $data['amount'];

            $due = DuePayment::where('shop_id', $shopId)
                ->where('customer_id', $customer->id)
                ->where('remaining_amount', '>', 0)
                ->when(!empty($data['sale_id']), fn($q) => $q->where('sale_id', $data['sale_id']))
                ->when(!empty($data['repair_id']), fn($q) => $q->where('repair_id', $data['repair_id']))
                ->orderBy('id')
                ->firstOrFail();

            $payAmount = min($amount, (float)$due->remaining_amount);
            $due->paid_amount = (float)$due->paid_amount + $payAmount;
            $due->remaining_amount = (float)$due->remaining_amount - $payAmount;
            $due->status = $due->remaining_amount <= 0 ? 'Paid' : 'Partial';
            $due->save();

            if ($due->sale_id) {
                Sale::where('id', $due->sale_id)->increment('paid_amount', $payAmount);
                $sale = Sale::find($due->sale_id);
                $sale->due_amount = max(0, (float)$sale->due_amount - $payAmount);
                $sale->payment_status = $sale->due_amount <= 0 ? 'Paid' : 'Partial';
                $sale->save();
            }

            if ($due->repair_id) {
                $repair = Repair::find($due->repair_id);
                $repair->remaining_amount = max(0, (float)$repair->remaining_amount - $payAmount);
                $repair->save();
            }

            $customer->increment('total_paid', $payAmount);
            $customer->decrement('total_due', $payAmount);
            $customer->update(['last_payment_date' => now()]);

            return Payment::create([
                'shop_id' => $shopId,
                'customer_id' => $customer->id,
                'user_id' => $userId,
                'payable_type' => $due->sale_id ? Sale::class : Repair::class,
                'payable_id' => $due->sale_id ?: $due->repair_id,
                'payment_no' => $this->numberService->paymentNo($shopId),
                'amount' => $payAmount,
                'method' => $data['method'] ?? 'cash',
                'type' => 'due_collection',
                'note' => $data['note'] ?? 'Due collection',
                'paid_at' => now(),
            ]);
        });
    }
}
