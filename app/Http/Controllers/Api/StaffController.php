<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\ResolvesShop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
class StaffController extends Controller
{
    use ResolvesShop;
    public function index(){ return User::where('shop_id',$this->shopId())->where('role','staff')->latest()->paginate(30); }
    public function store(Request $r){ $data=$r->validate(['name'=>'required|string','email'=>'required|email|unique:users,email','mobile'=>'nullable|string','password'=>'required|min:6','permissions'=>'nullable|array','status'=>'nullable|string']); return User::create(['shop_id'=>$this->shopId(),'name'=>$data['name'],'email'=>$data['email'],'mobile'=>$data['mobile']??null,'password'=>Hash::make($data['password']),'role'=>'staff','status'=>$data['status']??'Active','permissions'=>$data['permissions']??[]]); }
    public function show(User $staff){ abort_unless($staff->shop_id===$this->shopId() && $staff->role==='staff',403); return $staff; }
    public function update(Request $r, User $staff){ abort_unless($staff->shop_id===$this->shopId() && $staff->role==='staff',403); $data=$r->validate(['name'=>'sometimes|string','mobile'=>'nullable|string','password'=>'nullable|min:6','permissions'=>'nullable|array','status'=>'nullable|string']); if(!empty($data['password'])) $data['password']=Hash::make($data['password']); else unset($data['password']); $staff->update($data); return $staff; }
    public function destroy(User $staff){ abort_unless($staff->shop_id===$this->shopId() && $staff->role==='staff',403); $staff->delete(); return response()->json(['message'=>'Staff deleted.']); }
}
