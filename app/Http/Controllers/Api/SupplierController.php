<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\ResolvesShop;
use App\Models\Supplier;
use Illuminate\Http\Request;
class SupplierController extends Controller
{
    use ResolvesShop;
    public function index(Request $r) { return Supplier::where('shop_id',$this->shopId())->when($r->search,fn($q)=>$q->where('name','like','%'.$r->search.'%')->orWhere('mobile','like','%'.$r->search.'%'))->latest()->paginate(30); }
    public function store(Request $r) { $data=$r->validate(['name'=>'required|string','mobile'=>'nullable|string','address'=>'nullable|string','product_supplied'=>'nullable|string','total_purchase'=>'nullable|numeric','paid_amount'=>'nullable|numeric','due_amount'=>'nullable|numeric','status'=>'nullable|string']); $data['shop_id']=$this->shopId(); return Supplier::create($data); }
    public function show(Supplier $supplier) { abort_unless($supplier->shop_id===$this->shopId(),403); return $supplier->load('products'); }
    public function update(Request $r, Supplier $supplier) { abort_unless($supplier->shop_id===$this->shopId(),403); $supplier->update($r->all()); return $supplier; }
    public function destroy(Supplier $supplier) { abort_unless($supplier->shop_id===$this->shopId(),403); $supplier->delete(); return response()->json(['message'=>'Supplier deleted.']); }
}
