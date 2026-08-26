<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\ResolvesShop;
use App\Models\DuePayment;
use App\Services\DueService;
use Illuminate\Http\Request;
class DueController extends Controller
{
    use ResolvesShop;
    public function index(Request $r) { return DuePayment::with('customer')->where('shop_id',$this->shopId())->where('remaining_amount','>',0)->when($r->customer_id,fn($q)=>$q->where('customer_id',$r->customer_id))->latest()->paginate(30); }
    public function collect(Request $r, DueService $service) { $data=$r->validate(['customer_id'=>'required|exists:customers,id','sale_id'=>'nullable|exists:sales,id','repair_id'=>'nullable|exists:repairs,id','amount'=>'required|numeric|min:1','method'=>'nullable|string','note'=>'nullable|string']); return $service->collect($data,$this->shopId(),$r->user()->id); }
}
