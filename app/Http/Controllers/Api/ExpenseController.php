<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\ResolvesShop;
use App\Models\Expense;
use Illuminate\Http\Request;
class ExpenseController extends Controller
{
    use ResolvesShop;
    public function index(Request $r){ return Expense::where('shop_id',$this->shopId())->when($r->from,fn($q)=>$q->whereDate('expense_date','>=',$r->from))->when($r->to,fn($q)=>$q->whereDate('expense_date','<=',$r->to))->latest()->paginate(30); }
    public function store(Request $r){ $data=$r->validate(['category'=>'required|string','title'=>'required|string','amount'=>'required|numeric','expense_date'=>'required|date','payment_method'=>'nullable|string','note'=>'nullable|string']); $data['shop_id']=$this->shopId(); $data['user_id']=$r->user()->id; return Expense::create($data); }
    public function show(Expense $expense){ abort_unless($expense->shop_id===$this->shopId(),403); return $expense; }
    public function update(Request $r, Expense $expense){ abort_unless($expense->shop_id===$this->shopId(),403); $expense->update($r->all()); return $expense; }
    public function destroy(Expense $expense){ abort_unless($expense->shop_id===$this->shopId(),403); $expense->delete(); return response()->json(['message'=>'Expense deleted.']); }
}
