<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\ResolvesShop;
use App\Models\Product;
use App\Models\StockHistory;
use App\Services\StockService;
use Illuminate\Http\Request;
class StockController extends Controller
{
    use ResolvesShop;
    public function history(Request $r) { return StockHistory::with('product')->where('shop_id',$this->shopId())->when($r->product_id,fn($q)=>$q->where('product_id',$r->product_id))->latest()->paginate(50); }
    public function stockIn(Request $r, StockService $service) { $data=$r->validate(['product_id'=>'required|exists:products,id','quantity'=>'required|numeric|min:0.01','note'=>'nullable|string']); $product=Product::where('shop_id',$this->shopId())->findOrFail($data['product_id']); return $service->move($product,$data['quantity'],'in',$r->user()->id,$data['note']??'Stock in'); }
    public function stockOut(Request $r, StockService $service) { $data=$r->validate(['product_id'=>'required|exists:products,id','quantity'=>'required|numeric|min:0.01','type'=>'nullable|string','note'=>'nullable|string']); $product=Product::where('shop_id',$this->shopId())->findOrFail($data['product_id']); return $service->move($product,$data['quantity'],$data['type']??'out',$r->user()->id,$data['note']??'Stock out'); }
}
