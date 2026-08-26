<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\ResolvesShop;
use App\Models\Customer;
use App\Models\DuePayment;
use App\Models\Payment;
use App\Models\Repair;
use App\Services\InvoiceNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class RepairController extends Controller
{
    use ResolvesShop;
    public function index(Request $r) { return Repair::with('customer','assignedUser')->where('shop_id',$this->shopId())->when($r->status,fn($q)=>$q->where('status',$r->status))->when($r->due_today,fn($q)=>$q->whereDate('expected_return_date',today()))->latest()->paginate(30); }
    public function store(Request $r, InvoiceNumberService $numbers) {
        $data=$r->validate(['customer_id'=>'required|exists:customers,id','assigned_user_id'=>'nullable|exists:users,id','item_name'=>'required|string','brand_model'=>'nullable|string','problem_description'=>'required|string','item_condition'=>'nullable|string','received_date'=>'nullable|date','expected_return_date'=>'required|date','estimated_amount'=>'required|numeric','advance_amount'=>'nullable|numeric','parts_used'=>'nullable|string','service_charge'=>'nullable|numeric','notes'=>'nullable|string']);
        return DB::transaction(function() use($data,$r,$numbers){
            $estimated=(float)$data['estimated_amount']; $advance=(float)($data['advance_amount']??0); $remaining=max(0,$estimated-$advance);
            $repair=Repair::create(array_merge($data,['shop_id'=>$this->shopId(),'repair_no'=>$numbers->repairNo($this->shopId()),'received_date'=>$data['received_date']??today(),'advance_amount'=>$advance,'remaining_amount'=>$remaining,'status'=>'Received','delivery_status'=>'Pending']));
            $customer=Customer::where('shop_id',$this->shopId())->find($data['customer_id']);
            $customer->increment('total_purchase',$estimated); $customer->increment('total_paid',$advance); $customer->increment('total_due',$remaining);
            if($advance>0){ Payment::create(['shop_id'=>$this->shopId(),'customer_id'=>$customer->id,'user_id'=>$r->user()->id,'payable_type'=>Repair::class,'payable_id'=>$repair->id,'payment_no'=>$numbers->paymentNo($this->shopId()),'amount'=>$advance,'method'=>'cash','type'=>'repair_advance','note'=>'Repair advance','paid_at'=>now()]); }
            if($remaining>0){ DuePayment::create(['shop_id'=>$this->shopId(),'customer_id'=>$customer->id,'repair_id'=>$repair->id,'amount'=>$remaining,'paid_amount'=>0,'remaining_amount'=>$remaining,'reminder_date'=>$data['expected_return_date'],'status'=>'Due','note'=>'Repair remaining amount']); }
            return $repair->load('customer','assignedUser');
        });
    }
    public function show(Repair $repair) { abort_unless($repair->shop_id===$this->shopId(),403); return $repair->load(['customer','assignedUser','payments']); }
    public function update(Request $r, Repair $repair) { abort_unless($repair->shop_id===$this->shopId(),403); $repair->update($r->all()); return $repair->fresh('customer','assignedUser'); }
    public function destroy(Repair $repair) { abort_unless($repair->shop_id===$this->shopId(),403); $repair->delete(); return response()->json(['message'=>'Repair deleted.']); }
    public function updateStatus(Request $r, Repair $repair) { abort_unless($repair->shop_id===$this->shopId(),403); $data=$r->validate(['status'=>'required|in:Received,In Progress,Waiting for Parts,Repaired,Delivered,Cancelled','delivery_status'=>'nullable|string']); $repair->update($data + ['delivery_status'=>$data['status']==='Delivered'?'Delivered':($data['delivery_status']??$repair->delivery_status)]); return $repair; }
    public function collectPayment(Request $r, Repair $repair, \App\Services\DueService $dueService) { abort_unless($repair->shop_id===$this->shopId(),403); $data=$r->validate(['amount'=>'required|numeric|min:1','method'=>'nullable|string','note'=>'nullable|string']); $data['customer_id']=$repair->customer_id; $data['repair_id']=$repair->id; return $dueService->collect($data,$this->shopId(),$r->user()->id); }
}
