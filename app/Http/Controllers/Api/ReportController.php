<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\ResolvesShop;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Repair;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ReportController extends Controller
{
    use ResolvesShop;
    public function sales(Request $r, ReportService $svc){ [$from,$to]=$svc->range($r->all()); return Sale::with('customer')->where('shop_id',$this->shopId())->whereBetween('sale_date',[$from,$to])->latest()->paginate(100); }
    public function profit(Request $r, ReportService $svc){ [$from,$to]=$svc->range($r->all()); $itemProfit=SaleItem::whereHas('sale',fn($q)=>$q->where('shop_id',$this->shopId())->whereBetween('sale_date',[$from,$to]))->sum('profit'); $expense=Expense::where('shop_id',$this->shopId())->whereBetween('expense_date',[$from,$to])->sum('amount'); return response()->json(['gross_profit'=>$itemProfit,'expense'=>$expense,'net_profit'=>$itemProfit-$expense]); }
    public function stock(){ return Product::with('category')->where('shop_id',$this->shopId())->select('*',DB::raw('(quantity * purchase_price) as stock_value'))->paginate(100); }
    public function customerDue(){ return Customer::where('shop_id',$this->shopId())->where('total_due','>',0)->orderByDesc('total_due')->paginate(100); }
    public function repair(Request $r, ReportService $svc){ [$from,$to]=$svc->range($r->all()); return Repair::with('customer')->where('shop_id',$this->shopId())->whereBetween('received_date',[$from,$to])->latest()->paginate(100); }
    public function expense(Request $r, ReportService $svc){ [$from,$to]=$svc->range($r->all()); return Expense::where('shop_id',$this->shopId())->whereBetween('expense_date',[$from,$to])->latest()->paginate(100); }
}
