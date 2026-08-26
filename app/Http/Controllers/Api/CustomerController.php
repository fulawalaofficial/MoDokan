<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\ResolvesShop;
use App\Models\Customer;
use Illuminate\Http\Request;
class CustomerController extends Controller
{
    use ResolvesShop;
    public function index(Request $r) { return Customer::where('shop_id',$this->shopId())->when($r->search,fn($q)=>$q->where('name','like','%'.$r->search.'%')->orWhere('mobile','like','%'.$r->search.'%'))->when($r->due,fn($q)=>$q->where('total_due','>',0))->latest()->paginate(30); }
    public function store(Request $r) { $data=$r->validate(['name'=>'required|string','mobile'=>'nullable|string','address'=>'nullable|string','photo'=>'nullable|string','notes'=>'nullable|string','status'=>'nullable|string']); $data['shop_id']=$this->shopId(); return Customer::create($data); }
    public function show(Customer $customer) { abort_unless($customer->shop_id===$this->shopId(),403); return $customer->load(['sales.items','payments','repairs']); }
    public function update(Request $r, Customer $customer) { abort_unless($customer->shop_id===$this->shopId(),403); $customer->update($r->validate(['name'=>'sometimes|string','mobile'=>'nullable|string','address'=>'nullable|string','photo'=>'nullable|string','notes'=>'nullable|string','status'=>'nullable|string'])); return $customer; }
    public function destroy(Customer $customer) { abort_unless($customer->shop_id===$this->shopId(),403); $customer->delete(); return response()->json(['message'=>'Customer deleted.']); }
    public function ledger(Customer $customer) { abort_unless($customer->shop_id===$this->shopId(),403); return response()->json(['customer'=>$customer,'sales'=>$customer->sales()->latest()->get(),'payments'=>$customer->payments()->latest()->get(),'repairs'=>$customer->repairs()->latest()->get()]); }
}
