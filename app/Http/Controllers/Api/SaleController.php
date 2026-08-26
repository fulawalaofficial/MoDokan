<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\ResolvesShop;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\Request;
class SaleController extends Controller
{
    use ResolvesShop;
    public function index(Request $r) { return Sale::with('customer')->where('shop_id',$this->shopId())->when($r->from,fn($q)=>$q->whereDate('sale_date','>=',$r->from))->when($r->to,fn($q)=>$q->whereDate('sale_date','<=',$r->to))->latest()->paginate(30); }
    public function store(Request $r, SaleService $service) { $data=$r->validate(['customer_id'=>'required|exists:customers,id','items'=>'required|array|min:1','items.*.product_id'=>'required|exists:products,id','items.*.quantity'=>'required|numeric|min:0.01','items.*.selling_price'=>'nullable|numeric','items.*.discount'=>'nullable|numeric','discount'=>'nullable|numeric','tax'=>'nullable|numeric','paid_amount'=>'nullable|numeric','payment_method'=>'nullable|string','due_reminder_date'=>'nullable|date','sale_date'=>'nullable|date','notes'=>'nullable|string']); return $service->createSale($data,$this->shopId(),$r->user()->id); }
    public function show(Sale $sale) { abort_unless($sale->shop_id===$this->shopId(),403); return $sale->load(['customer','items.product','payments']); }
    public function returnSale(Sale $sale) { abort_unless($sale->shop_id===$this->shopId(),403); $sale->update(['sale_status'=>'Returned']); return response()->json(['message'=>'Sale marked as returned. Add stock return adjustment as needed.']); }
}
