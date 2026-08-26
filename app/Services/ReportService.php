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
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $todaySales = Sale::where('shop_id', $shopId)->whereBetween('sale_date', [$todayStart, $todayEnd])->sum('total_amount');
        $todayPaid = Sale::where('shop_id', $shopId)->whereBetween('sale_date', [$todayStart, $todayEnd])->sum('paid_amount');
        $todayDue = Sale::where('shop_id', $shopId)->whereBetween('sale_date', [$todayStart, $todayEnd])->sum('due_amount');
        $todayExpense = Expense::where('shop_id', $shopId)->whereDate('expense_date', today())->sum('amount');
        $todayProfit = SaleItem::whereHas('sale', fn($q) => $q->where('shop_id', $shopId)->whereBetween('sale_date', [$todayStart, $todayEnd]))->sum('profit') - $todayExpense;

        return [
            'today_sales' => round($todaySales, 2),
            'today_paid' => round($todayPaid, 2),
            'today_due' => round($todayDue, 2),
            'today_expense' => round($todayExpense, 2),
            'today_profit' => round($todayProfit, 2),
            'total_customers' => Customer::where('shop_id', $shopId)->count(),
            'total_products' => Product::where('shop_id', $shopId)->count(),
            'total_stock_value' => Product::where('shop_id', $shopId)->select(DB::raw('COALESCE(SUM(quantity * purchase_price),0) as value'))->value('value'),
            'low_stock_products' => Product::where('shop_id', $shopId)->whereColumn('quantity', '<=', 'low_stock_alert')->count(),
            'repair_pending' => Repair::where('shop_id', $shopId)->whereNotIn('status', ['Delivered','Cancelled'])->count(),
            'repair_due_today' => Repair::where('shop_id', $shopId)->whereDate('expected_return_date', today())->where('delivery_status', 'Pending')->count(),
            'recent_transactions' => Sale::where('shop_id', $shopId)->with('customer')->latest()->limit(5)->get(),
            'recent_customers' => Customer::where('shop_id', $shopId)->latest()->limit(5)->get(),
            'monthly_sales_chart' => $this->monthlySaleChart($shopId),
            'monthly_profit_chart' => $this->monthlyProfitChart($shopId),
        ];
    }

    public function monthlySaleChart(int $shopId): array
    {
        return Sale::where('shop_id', $shopId)
            ->where('sale_date', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw('DATE_FORMAT(sale_date, "%Y-%m") as month, SUM(total_amount) as total')
            ->groupBy('month')->orderBy('month')->get()->toArray();
    }

    public function monthlyProfitChart(int $shopId): array
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.shop_id', $shopId)
            ->where('sales.sale_date', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw('DATE_FORMAT(sales.sale_date, "%Y-%m") as month, SUM(sale_items.profit) as profit')
            ->groupBy('month')->orderBy('month')->get()->toArray();
    }
}
