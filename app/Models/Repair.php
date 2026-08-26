<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Repair extends Model
{
    protected $fillable = ['shop_id','customer_id','assigned_user_id','repair_no','item_name','brand_model','problem_description','item_condition','received_date','expected_return_date','estimated_amount','advance_amount','remaining_amount','parts_used','service_charge','status','delivery_status','notes'];
    protected $casts = ['received_date'=>'date','expected_return_date'=>'date','estimated_amount'=>'decimal:2','advance_amount'=>'decimal:2','remaining_amount'=>'decimal:2','service_charge'=>'decimal:2'];
    public function customer() { return $this->belongsTo(Customer::class); }
    public function assignedUser() { return $this->belongsTo(User::class, 'assigned_user_id'); }
    public function payments() { return $this->morphMany(Payment::class, 'payable'); }
}
