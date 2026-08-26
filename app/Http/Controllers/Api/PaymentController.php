<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\ResolvesShop;
use App\Models\Payment;
use Illuminate\Http\Request;
class PaymentController extends Controller
{
    use ResolvesShop;
    public function index(Request $r) { return Payment::with('customer')->where('shop_id',$this->shopId())->when($r->from,fn($q)=>$q->whereDate('paid_at','>=',$r->from))->when($r->to,fn($q)=>$q->whereDate('paid_at','<=',$r->to))->latest()->paginate(50); }
}
