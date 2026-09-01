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
        $from = Carbon::parse(
            $filters['from'] ?? now()->startOfMonth()
        )->startOfDay();

        $to = Carbon::parse(
            $filters['to'] ?? now()
        )->endOfDay();

        return [$from, $to];
    }

    public function dashboard(int $shopId): array
    {
        $todayStart = now()->copy()->startOfDay();
        $todayEnd = now()->copy()->endOfDay();

        $salesDateColumn = $this->firstExistingColumn(
            'sales',
            ['sale_date', 'created_at']
        );

        $expenseDateColumn = $this->firstExistingColumn(
            'expenses',
            ['expense_date', 'created_at']
        );

        $todaySales = $this->safe(
            'today_sales',
            0.0,
            function () use (
                $shopId,
                $todayStart,
                $todayEnd,
                $salesDateColumn
            ) {
                if (
                    !$salesDateColumn ||
                    !$this->hasColumns(
                        'sales',
                        ['shop_id', 'total_amount']
                    )
                ) {
                    return 0.0;
                }

                return (float) Sale::query()
                    ->where('shop_id', $shopId)
                    ->whereBetween(
                        $salesDateColumn,
                        [$todayStart, $todayEnd]
                    )
                    ->sum('total_amount');
            }
        );

        $todayPaid = $this->safe(
            'today_paid',
            0.0,
            function () use (
                $shopId,
                $todayStart,
                $todayEnd,
                $salesDateColumn
            ) {
                if (
                    !$salesDateColumn ||
                    !$this->hasColumns(
                        'sales',
                        ['shop_id', 'paid_amount']
                    )
                ) {
                    return 0.0;
                }

                return (float) Sale::query()
                    ->where('shop_id', $shopId)
                    ->whereBetween(
                        $salesDateColumn,
                        [$todayStart, $todayEnd]
                    )
                    ->sum('paid_amount');
            }
        );

        $todayDue = $this->safe(
            'today_due',
            0.0,
            function () use (
                $shopId,
                $todayStart,
                $todayEnd,
                $salesDateColumn
            ) {
                if (
                    !$salesDateColumn ||
                    !$this->hasColumns(
                        'sales',
                        ['shop_id', 'due_amount']
                    )
                ) {
                    return 0.0;
                }

                return (float) Sale::query()
                    ->where('shop_id', $shopId)
                    ->whereBetween(
                        $salesDateColumn,
                        [$todayStart, $todayEnd]
                    )
                    ->sum('due_amount');
            }
        );

        $todayExpense = $this->safe(
            'today_expense',
            0.0,
            function () use (
                $shopId,
                $todayStart,
                $todayEnd,
                $expenseDateColumn
            ) {
                if (
                    !$expenseDateColumn ||
                    !$this->hasColumns(
                        'expenses',
                        ['shop_id', 'amount']
                    )
                ) {
                    return 0.0;
                }

                return (float) Expense::query()
                    ->where('shop_id', $shopId)
                    ->whereBetween(
                        $expenseDateColumn,
                        [$todayStart, $todayEnd]
                    )
                    ->sum('amount');
            }
        );

        $grossProfit = $this->safe(
            'today_gross_profit',
            0.0,
            fn () => $this->todayGrossProfit(
                $shopId,
                $todayStart,
                $todayEnd,
                $salesDateColumn
            )
        );

        $totalCustomers = $this->safe(
            'total_customers',
            0,
            function () use ($shopId) {
                if (
                    !$this->hasColumns(
                        'customers',
                        ['shop_id']
                    )
                ) {
                    return 0;
                }

                return Customer::query()
                    ->where('shop_id', $shopId)
                    ->count();
            }
        );

        $totalProducts = $this->safe(
            'total_products',
            0,
            function () use ($shopId) {
                if (
                    !$this->hasColumns(
                        'products',
                        ['shop_id']
                    )
                ) {
                    return 0;
                }

                return Product::query()
                    ->where('shop_id', $shopId)
                    ->count();
            }
        );

        $stockValue = $this->safe(
            'total_stock_value',
            0.0,
            function () use ($shopId) {
                if (
                    !$this->hasColumns(
                        'products',
                        [
                            'shop_id',
                            'quantity',
                            'purchase_price',
                        ]
                    )
                ) {
                    return 0.0;
                }

                return (float) Product::query()
                    ->where('shop_id', $shopId)
                    ->selectRaw(
                        'COALESCE(SUM(quantity * purchase_price), 0) AS stock_value'
                    )
                    ->value('stock_value');
            }
        );

        $lowStock = $this->safe(
            'low_stock_products',
            0,
            function () use ($shopId) {
                if (
                    !$this->hasColumns(
                        'products',
                        [
                            'shop_id',
                            'quantity',
                            'low_stock_alert',
                        ]
                    )
                ) {
                    return 0;
                }

                return Product::query()
                    ->where('shop_id', $shopId)
                    ->whereColumn(
                        'quantity',
                        '<=',
                        'low_stock_alert'
                    )
                    ->count();
            }
        );

        $repairPending = $this->safe(
            'repair_pending',
            0,
            function () use ($shopId) {
                if (
                    !$this->hasColumns(
                        'repairs',
                        ['shop_id', 'status']
                    )
                ) {
                    return 0;
                }

                return Repair::query()
                    ->where('shop_id', $shopId)
                    ->whereNotIn(
                        'status',
                        ['Delivered', 'Cancelled']
                    )
                    ->count();
            }
        );

        $repairDueToday = $this->safe(
            'repair_due_today',
            0,
            function () use ($shopId) {
                if (
                    !$this->hasColumns(
                        'repairs',
                        [
                            'shop_id',
                            'expected_return_date',
                            'delivery_status',
                        ]
                    )
                ) {
                    return 0;
                }

                return Repair::query()
                    ->where('shop_id', $shopId)
                    ->whereDate(
                        'expected_return_date',
                        today()
                    )
                    ->where(
                        'delivery_status',
                        'Pending'
                    )
                    ->count();
            }
        );

        $recentTransactions = $this->safe(
            'recent_transactions',
            [],
            function () use (
                $shopId,
                $salesDateColumn
            ) {
                if (
                    !$this->hasColumns(
                        'sales',
                        ['shop_id']
                    )
                ) {
                    return [];
                }

                $query = Sale::query()
                    ->where('shop_id', $shopId)
                    ->limit(5);

                if ($salesDateColumn) {
                    $query->orderByDesc($salesDateColumn);
                } else {
                    $query->orderByDesc('id');
                }

                /*
                 * Only eager-load customer when the Sale model actually has
                 * the relationship. This prevents a missing relation from
                 * breaking the dashboard.
                 */
                if (method_exists(new Sale(), 'customer')) {
                    $query->with('customer');
                }

                return $query->get()->values()->all();
            }
        );

        $recentCustomers = $this->safe(
            'recent_customers',
            [],
            function () use ($shopId) {
                if (
                    !$this->hasColumns(
                        'customers',
                        ['shop_id']
                    )
                ) {
                    return [];
                }

                return Customer::query()
                    ->where('shop_id', $shopId)
                    ->orderByDesc('id')
                    ->limit(5)
                    ->get()
                    ->values()
                    ->all();
            }
        );

        return [
            'today_sales' => round(
                (float) $todaySales,
                2
            ),

            'today_paid' => round(
                (float) $todayPaid,
                2
            ),

            'today_due' => round(
                (float) $todayDue,
                2
            ),

            'today_expense' => round(
                (float) $todayExpense,
                2
            ),

            'today_profit' => round(
                (float) $grossProfit -
                (float) $todayExpense,
                2
            ),

            'total_customers' =>
                (int) $totalCustomers,

            'total_products' =>
                (int) $totalProducts,

            'total_stock_value' => round(
                (float) $stockValue,
                2
            ),

            'low_stock_products' =>
                (int) $lowStock,

            'repair_pending' =>
                (int) $repairPending,

            'repair_due_today' =>
                (int) $repairDueToday,

            'recent_transactions' =>
                $recentTransactions,

            'recent_customers' =>
                $recentCustomers,

            'monthly_sales_chart' =>
                $this->safe(
                    'monthly_sales_chart',
                    [],
                    fn () => $this->monthlySaleChart(
                        $shopId
                    )
                ),

            'monthly_profit_chart' =>
                $this->safe(
                    'monthly_profit_chart',
                    [],
                    fn () => $this->monthlyProfitChart(
                        $shopId
                    )
                ),
        ];
    }

    private function todayGrossProfit(
        int $shopId,
        Carbon $from,
        Carbon $to,
        ?string $salesDateColumn = null
    ): float {
        if (
            !$salesDateColumn ||
            !$this->hasColumns(
                'sales',
                ['id', 'shop_id']
            ) ||
            !$this->hasColumns(
                'sale_items',
                ['sale_id']
            )
        ) {
            return 0.0;
        }

        if ($this->hasColumn(
            'sale_items',
            'profit'
        )) {
            return (float) SaleItem::query()
                ->join(
                    'sales',
                    'sales.id',
                    '=',
                    'sale_items.sale_id'
                )
                ->where(
                    'sales.shop_id',
                    $shopId
                )
                ->whereBetween(
                    "sales.{$salesDateColumn}",
                    [$from, $to]
                )
                ->sum('sale_items.profit');
        }

        if (
            !$this->hasColumns(
                'sale_items',
                [
                    'quantity',
                    'purchase_price',
                    'selling_price',
                ]
            )
        ) {
            return 0.0;
        }

        $discount = $this->hasColumn(
            'sale_items',
            'discount'
        )
            ? 'COALESCE(sale_items.discount, 0)'
            : '0';

        return (float) SaleItem::query()
            ->join(
                'sales',
                'sales.id',
                '=',
                'sale_items.sale_id'
            )
            ->where(
                'sales.shop_id',
                $shopId
            )
            ->whereBetween(
                "sales.{$salesDateColumn}",
                [$from, $to]
            )
            ->selectRaw(
                "COALESCE(
                    SUM(
                        (
                            (
                                sale_items.selling_price -
                                sale_items.purchase_price
                            ) *
                            sale_items.quantity
                        ) -
                        {$discount}
                    ),
                    0
                ) AS profit"
            )
            ->value('profit');
    }

    public function monthlySaleChart(
        int $shopId
    ): array {
        $salesDateColumn = $this->firstExistingColumn(
            'sales',
            ['sale_date', 'created_at']
        );

        if (
            !$salesDateColumn ||
            !$this->hasColumns(
                'sales',
                [
                    'shop_id',
                    'total_amount',
                ]
            )
        ) {
            return [];
        }

        $qualifiedDate =
            "sales.{$salesDateColumn}";

        $month = $this->monthExpression(
            $qualifiedDate
        );

        return Sale::query()
            ->where(
                'sales.shop_id',
                $shopId
            )
            ->where(
                $qualifiedDate,
                '>=',
                now()
                    ->subMonths(11)
                    ->startOfMonth()
            )
            ->selectRaw(
                "{$month} AS month,
                 SUM(sales.total_amount) AS total"
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'month' => $row->month,
                'total' => round(
                    (float) $row->total,
                    2
                ),
            ])
            ->values()
            ->all();
    }

    public function monthlyProfitChart(
        int $shopId
    ): array {
        $salesDateColumn = $this->firstExistingColumn(
            'sales',
            ['sale_date', 'created_at']
        );

        if (
            !$salesDateColumn ||
            !$this->hasColumns(
                'sales',
                ['id', 'shop_id']
            ) ||
            !$this->hasColumns(
                'sale_items',
                ['sale_id']
            )
        ) {
            return [];
        }

        $qualifiedDate =
            "sales.{$salesDateColumn}";

        $month = $this->monthExpression(
            $qualifiedDate
        );

        if (
            $this->hasColumn(
                'sale_items',
                'profit'
            )
        ) {
            return SaleItem::query()
                ->join(
                    'sales',
                    'sales.id',
                    '=',
                    'sale_items.sale_id'
                )
                ->where(
                    'sales.shop_id',
                    $shopId
                )
                ->where(
                    $qualifiedDate,
                    '>=',
                    now()
                        ->subMonths(11)
                        ->startOfMonth()
                )
                ->selectRaw(
                    "{$month} AS month,
                     SUM(sale_items.profit) AS profit"
                )
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->map(fn ($row) => [
                    'month' => $row->month,
                    'profit' => round(
                        (float) $row->profit,
                        2
                    ),
                ])
                ->values()
                ->all();
        }

        if (
            !$this->hasColumns(
                'sale_items',
                [
                    'quantity',
                    'purchase_price',
                    'selling_price',
                ]
            )
        ) {
            return [];
        }

        $discount = $this->hasColumn(
            'sale_items',
            'discount'
        )
            ? 'COALESCE(sale_items.discount, 0)'
            : '0';

        return SaleItem::query()
            ->join(
                'sales',
                'sales.id',
                '=',
                'sale_items.sale_id'
            )
            ->where(
                'sales.shop_id',
                $shopId
            )
            ->where(
                $qualifiedDate,
                '>=',
                now()
                    ->subMonths(11)
                    ->startOfMonth()
            )
            ->selectRaw(
                "{$month} AS month,
                 SUM(
                    (
                        (
                            sale_items.selling_price -
                            sale_items.purchase_price
                        ) *
                        sale_items.quantity
                    ) -
                    {$discount}
                 ) AS profit"
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'month' => $row->month,
                'profit' => round(
                    (float) $row->profit,
                    2
                ),
            ])
            ->values()
            ->all();
    }

    private function monthExpression(
        string $column
    ): string {
        return match (
            DB::connection()->getDriverName()
        ) {
            'sqlite' =>
                "strftime('%Y-%m', {$column})",

            'pgsql' =>
                "to_char({$column}, 'YYYY-MM')",

            default =>
                "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }

    private function firstExistingColumn(
        string $table,
        array $columns
    ): ?string {
        foreach ($columns as $column) {
            if ($this->hasColumn(
                $table,
                $column
            )) {
                return $column;
            }
        }

        return null;
    }

    private function hasTable(
        string $table
    ): bool {
        try {
            return Schema::hasTable($table);
        } catch (Throwable $e) {
            Log::warning(
                'Dashboard schema table check failed',
                [
                    'table' => $table,
                    'message' => $e->getMessage(),
                ]
            );

            return false;
        }
    }

    private function hasColumn(
        string $table,
        string $column
    ): bool {
        try {
            return Schema::hasTable($table) &&
                Schema::hasColumn(
                    $table,
                    $column
                );
        } catch (Throwable $e) {
            Log::warning(
                'Dashboard schema column check failed',
                [
                    'table' => $table,
                    'column' => $column,
                    'message' => $e->getMessage(),
                ]
            );

            return false;
        }
    }

    private function hasColumns(
        string $table,
        array $columns
    ): bool {
        if (!$this->hasTable($table)) {
            return false;
        }

        foreach ($columns as $column) {
            if (
                !$this->hasColumn(
                    $table,
                    $column
                )
            ) {
                return false;
            }
        }

        return true;
    }

    private function safe(
        string $metric,
        mixed $default,
        callable $callback
    ): mixed {
        try {
            return $callback();
        } catch (Throwable $e) {
            Log::warning(
                'MoDokana dashboard metric failed',
                [
                    'metric' => $metric,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return $default;
        }
    }
}
