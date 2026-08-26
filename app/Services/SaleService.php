<?php
namespace App\Services;

use App\Models\Customer;
use App\Models\DuePayment;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(
        protected StockService $stockService,
        protected InvoiceNumberService $numberService
    ) {}

    public function createSale(array $data, int $shopId, int $userId): Sale
    {
        return DB::transaction(function () use ($data, $shopId, $userId) {
            $customer = Customer::where('shop_id', $shopId)->findOrFail($data['customer_id']);
            $items = collect($data['items'] ?? []);
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'At least one sale item is required.']);
            }

            $subtotal = 0;
            $profit = 0;
            $prepared = [];

            foreach ($items as $row) {
                $product = Product::where('shop_id', $shopId)->findOrFail($row['product_id']);
                $qty = (float) $row['quantity'];
                if ($qty <= 0) throw ValidationException::withMessages(['quantity' => 'Quantity must be greater than zero.']);
                if ((float)$product->quantity < $qty) throw ValidationException::withMessages(['stock' => $product->name.' has insufficient stock.']);
                $selling = (float) ($row['selling_price'] ?? $product->selling_price);
                $discount = (float) ($row['discount'] ?? 0);
                $lineTotal = ($selling * $qty) - $discount;
                $lineProfit = (($selling - (float)$product->purchase_price) * $qty) - $discount;
                $subtotal += $lineTotal;
                $profit += $lineProfit;
                $prepared[] = compact('product','qty','selling','discount','lineTotal','lineProfit');
            }

            $discount = (float) ($data['discount'] ?? 0);
            $tax = (float) ($data['tax'] ?? 0);
            $total = max(0, $subtotal - $discount + $tax);
            $paid = min((float) ($data['paid_amount'] ?? 0), $total);
            $due = $total - $paid;

            $sale = Sale::create([
                'shop_id' => $shopId,
                'customer_id' => $customer->id,
                'user_id' => $userId,
                'invoice_no' => $this->numberService->saleNo($shopId, request()->user()->shop->invoice_prefix ?? 'INV'),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total_amount' => $total,
                'paid_amount' => $paid,
                'due_amount' => $due,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'payment_status' => $due <= 0 ? 'Paid' : ($paid > 0 ? 'Partial' : 'Due'),
                'sale_status' => 'Completed',
                'sale_date' => $data['sale_date'] ?? now(),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($prepared as $row) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $row['product']->id,
                    'product_name' => $row['product']->name,
                    'quantity' => $row['qty'],
                    'purchase_price' => $row['product']->purchase_price,
                    'selling_price' => $row['selling'],
                    'discount' => $row['discount'],
                    'total' => $row['lineTotal'],
                    'profit' => $row['lineProfit'],
                ]);
                $this->stockService->move($row['product'], $row['qty'], 'sale', $userId, 'Sale '.$sale->invoice_no, Sale::class, $sale->id);
            }

            if ($paid > 0) {
                Payment::create([
                    'shop_id' => $shopId,
                    'customer_id' => $customer->id,
                    'user_id' => $userId,
                    'payable_type' => Sale::class,
                    'payable_id' => $sale->id,
                    'payment_no' => $this->numberService->paymentNo($shopId),
                    'amount' => $paid,
                    'method' => $data['payment_method'] ?? 'cash',
                    'type' => 'sale_payment',
                    'note' => 'Payment against sale '.$sale->invoice_no,
                    'paid_at' => now(),
                ]);
            }

            if ($due > 0) {
                DuePayment::create([
                    'shop_id' => $shopId,
                    'customer_id' => $customer->id,
                    'sale_id' => $sale->id,
                    'amount' => $due,
                    'paid_amount' => 0,
                    'remaining_amount' => $due,
                    'reminder_date' => $data['due_reminder_date'] ?? null,
                    'status' => 'Due',
                    'note' => 'Due for sale '.$sale->invoice_no,
                ]);
            }

            $customer->increment('total_purchase', $total);
            $customer->increment('total_paid', $paid);
            $customer->increment('total_due', $due);
            if ($paid > 0) $customer->update(['last_payment_date' => now()]);

            return $sale->load(['customer','items.product','payments']);
        });
    }
}
