<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\ResolvesShop;
use App\Models\Notification;
class NotificationController extends Controller
{
    use ResolvesShop;
    public function index(){ return Notification::where('shop_id',$this->shopId())->where(function($q){$q->whereNull('user_id')->orWhere('user_id',request()->user()->id);})->latest()->paginate(50); }
    public function markRead(Notification $notification){ abort_unless($notification->shop_id===$this->shopId(),403); $notification->update(['read_at'=>now()]); return response()->json(['message'=>'Notification read.']); }
}
