<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Repair;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ReportService
{
    public function range(array $filters): array
    {
        $from = Carbon::parse($filters['from'] ?? now()->startOfMonth())->startOfDay();
        $to = Carbon::parse($filters['to'] ?? now())->endOfDay();

        return [$from, $to];
    }

    public function dashboard(int $shopId): array
    {
        $todayStart = now()->copy()->startOfDay();
        $todayEnd = now()->copy()->endOfDay();

        $todaySales = $this->safe(
            fn () => (float) Sale::query()
                ->where('shop_id', $shopId)
                ->whereBetween('sale_date', [$todayStart, $todayEnd])
                ->sum('total_amount'),
            0.0,
            'today_sales'
        );

        $todayPaid = $this->safe(
            fn () => (float) Sale::query()
                ->where('shop_id', $shopId)
                ->whereBetween('sale_date', [$todayStart, $todayEnd])
                ->sum('paid_amount'),
            0.0,
            'today_paid'
        );

        $todayDue = $this->safe(
            fn () => (float) Sale::query()
                ->where('shop_id', $shopId)
                ->whereBetween('sale_date', [$todayStart, $todayEnd])
                ->sum('due_amount'),
            0.0,
            'today_due'
        );

        $todayExpense = $this->safe(
            fn () => (float) Expense::query()
                ->where('shop_id', $shopId)
                ->whereDate('expense_date', today())
                ->sum('amount'),
            0.0,
            'today_expense'
        );

        $grossProfit = $this->safe(
            fn () => $this->todayGrossProfit($shopId, $todayStart, $todayEnd),
            0.0,
            'today_gross_profit'
        );

        $totalCustomers = $this->safe(
            fn () => Customer::query()->where('shop_id', $shopId)->count(),
            0,
            'total_customers'
        );

        $totalProducts = $this->safe(
            fn () => Product::query()->where('shop_id', $shopId)->count(),
            0,
            'total_products'
        );

        $totalStockValue = $this->safe(
            fn () => (float) Product::query()
                ->where('shop_id', $shopId)
                ->selectRaw('COALESCE(SUM(quantity * purchase_price), 0) AS stock_value')
                ->value('stock_value'),
            0.0,
            'total_stock_value'
        );

        $lowStockProducts = $this->safe(
            function () use ($shopId) {
                if (!Schema::hasColumn('products', 'low_stock_alert')) {
                    return 0;
                }

                return Product::query()
                    ->where('shop_id', $shopId)
                    ->whereColumn('quantity', '<=', 'low_stock_alert')
                    ->count();
            },
            0,
            'low_stock_products'
        );

        $repairPending = $this->safe(
            fn () => Repair::query()
                ->where('shop_id', $shopId)
                ->whereNotIn('status', ['Delivered', 'Cancelled'])
                ->count(),
            0,
            'repair_pending'
        );

        $repairDueToday = $this->safe(
            function () use ($shopId) {
                if (
                    !Schema::hasColumn('repairs', 'expected_return_date') ||
                    !Schema::hasColumn('repairs', 'delivery_status')
                ) {
                    return 0;
                }

                return Repair::query()
                    ->where('shop_id', $shopId)
                    ->whereDate('expected_return_date', today())
                    ->where('delivery_status', 'Pending')
                    ->count();
            },
            0,
            'repair_due_today'
        );

        $recentTransactions = $this->safe(
            fn () => Sale::query()
                ->where('shop_id', $shopId)
                ->with('customer:id,name,mobile')
                ->orderByDesc('sale_date')
                ->limit(5)
                ->get(),
            collect(),
            'recent_transactions'
        );

        $recentCustomers = $this->safe(
            fn () => Customer::query()
                ->where('shop_id', $shopId)
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
            collect(),
            'recent_customers'
        );

        return [
            'today_sales' => round((float) $todaySales, 2),
            'today_paid' => round((float) $todayPaid, 2),
            'today_due' => round((float) $todayDue, 2),
            'today_expense' => round((float) $todayExpense, 2),
            'today_profit' => round((float) $grossProfit - (float) $todayExpense, 2),

            'total_customers' => (int) $totalCustomers,
            'total_products' => (int) $totalProducts,
            'total_stock_value' => round((float) $totalStockValue, 2),
            'low_stock_products' => (int) $lowStockProducts,

            'repair_pending' => (int) $repairPending,
            'repair_due_today' => (int) $repairDueToday,

            'recent_transactions' => $recentTransactions,
            'recent_customers' => $recentCustomers,

            'monthly_sales_chart' => $this->safe(
                fn () => $this->monthlySaleChart($shopId),
                [],
                'monthly_sales_chart'
            ),

            'monthly_profit_chart' => $this->safe(
                fn () => $this->monthlyProfitChart($shopId),
                [],
                'monthly_profit_chart'
            ),
        ];
    }

    private function todayGrossProfit(int $shopId, Carbon $from, Carbon $to): float
    {
        if (!Schema::hasTable('sale_items') || !Schema::hasTable('sales')) {
            return 0.0;
        }

        if (Schema::hasColumn('sale_items', 'profit')) {
            return (float) SaleItem::query()
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->where('sales.shop_id', $shopId)
                ->whereBetween('sales.sale_date', [$from, $to])
                ->sum('sale_items.profit');
        }

        /*
         * Compatibility fallback for older databases where the profit column
         * has not yet been created.
         */
        $required = ['quantity', 'purchase_price', 'selling_price'];

        foreach ($required as $column) {
            if (!Schema::hasColumn('sale_items', $column)) {
                return 0.0;
            }
        }

        $discountSql = Schema::hasColumn('sale_items', 'discount')
            ? 'COALESCE(sale_items.discount, 0)'
            : '0';

        return (float) SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.shop_id', $shopId)
            ->whereBetween('sales.sale_date', [$from, $to])
            ->selectRaw(
                "COALESCE(SUM(((sale_items.selling_price - sale_items.purchase_price) * sale_items.quantity) - {$discountSql}), 0) AS profit"
            )
            ->value('profit');
    }

    public function monthlySaleChart(int $shopId): array
    {
        $monthExpression = $this->monthExpression('sales.sale_date');

        return Sale::query()
            ->where('sales.shop_id', $shopId)
            ->where('sales.sale_date', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw("{$monthExpression} AS month, SUM(sales.total_amount) AS total")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'month' => $row->month,
                'total' => round((float) $row->total, 2),
            ])
            ->values()
            ->all();
    }

    public function monthlyProfitChart(int $shopId): array
    {
        if (!Schema::hasTable('sale_items') || !Schema::hasTable('sales')) {
            return [];
        }

        $monthExpression = $this->monthExpression('sales.sale_date');

        if (Schema::hasColumn('sale_items', 'profit')) {
            return SaleItem::query()
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->where('sales.shop_id', $shopId)
                ->where('sales.sale_date', '>=', now()->subMonths(11)->startOfMonth())
                ->selectRaw("{$monthExpression} AS month, SUM(sale_items.profit) AS profit")
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->map(fn ($row) => [
                    'month' => $row->month,
                    'profit' => round((float) $row->profit, 2),
                ])
                ->values()
                ->all();
        }

        $required = ['quantity', 'purchase_price', 'selling_price'];

        foreach ($required as $column) {
            if (!Schema::hasColumn('sale_items', $column)) {
                return [];
            }
        }

        $discountSql = Schema::hasColumn('sale_items', 'discount')
            ? 'COALESCE(sale_items.discount, 0)'
            : '0';

        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.shop_id', $shopId)
            ->where('sales.sale_date', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw(
                "{$monthExpression} AS month,
                 SUM(((sale_items.selling_price - sale_items.purchase_price) * sale_items.quantity) - {$discountSql}) AS profit"
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'month' => $row->month,
                'profit' => round((float) $row->profit, 2),
            ])
            ->values()
            ->all();
    }

    private function monthExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', {$column})",
            'pgsql' => "to_char({$column}, 'YYYY-MM')",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }

    private function safe(callable $callback, mixed $default, string $metric): mixed
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            /*
             * One broken/old dashboard column should not make the entire mobile
             * dashboard return HTTP 500. The failing metric is logged and a
             * safe default is returned.
             */
            Log::warning('Dashboard metric failed', [
                'metric' => $metric,
                'message' => $e->getMessage(),
            ]);

            return $default;
        }
    }
}
