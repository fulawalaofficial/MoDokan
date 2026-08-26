<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\ResolvesShop;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
class ProductCategoryController extends Controller
{
    use ResolvesShop;
    public function index() { return ProductCategory::where('shop_id',$this->shopId())->latest()->paginate(30); }
    public function store(Request $r) { $data=$r->validate(['name'=>'required|string','description'=>'nullable|string','status'=>'nullable|string']); $data['shop_id']=$this->shopId(); return ProductCategory::create($data); }
    public function show(ProductCategory $productCategory) { abort_unless($productCategory->shop_id===$this->shopId(),403); return $productCategory; }
    public function update(Request $r, ProductCategory $productCategory) { abort_unless($productCategory->shop_id===$this->shopId(),403); $productCategory->update($r->validate(['name'=>'sometimes|string','description'=>'nullable|string','status'=>'nullable|string'])); return $productCategory; }
    public function destroy(ProductCategory $productCategory) { abort_unless($productCategory->shop_id===$this->shopId(),403); $productCategory->delete(); return response()->json(['message'=>'Category deleted.']); }
}
