<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\ResolvesShop;
use App\Models\Product;
use Illuminate\Http\Request;
class ProductController extends Controller
{
    use ResolvesShop;
    public function index(Request $r) {
        return Product::with(['category','supplier'])->where('shop_id',$this->shopId())
            ->when($r->search, fn($q)=>$q->where('name','like','%'.$r->search.'%')->orWhere('sku','like','%'.$r->search.'%'))
            ->when($r->low_stock, fn($q)=>$q->whereColumn('quantity','<=','low_stock_alert'))
            ->latest()->paginate(30);
    }
    public function store(Request $r) { $data=$this->validated($r); $data['shop_id']=$this->shopId(); $data['opening_stock']=$data['quantity']??0; return Product::create($data)->load('category'); }
    public function show(Product $product) { abort_unless($product->shop_id===$this->shopId(),403); return $product->load('category','supplier','stockHistories'); }
    public function update(Request $r, Product $product) { abort_unless($product->shop_id===$this->shopId(),403); $product->update($this->validated($r, true)); return $product->fresh('category'); }
    public function destroy(Product $product) { abort_unless($product->shop_id===$this->shopId(),403); $product->delete(); return response()->json(['message'=>'Product deleted.']); }
    private function validated(Request $r, bool $update=false): array { return $r->validate([
        'product_category_id'=>[$update?'sometimes':'required','exists:product_categories,id'],'supplier_id'=>'nullable|exists:suppliers,id','name'=>[$update?'sometimes':'required','string'],
        'sku'=>'nullable|string','barcode'=>'nullable|string','image'=>'nullable|string','purchase_price'=>[$update?'sometimes':'required','numeric'],'selling_price'=>[$update?'sometimes':'required','numeric'],
        'quantity'=>[$update?'sometimes':'required','numeric'],'unit_type'=>'nullable|string','low_stock_alert'=>'nullable|numeric','status'=>'nullable|string'
    ]); }
}
