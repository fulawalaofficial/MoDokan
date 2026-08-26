<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Payment extends Model
{
    protected $fillable = ['shop_id','customer_id','user_id','payable_type','payable_id','payment_no','amount','method','type','note','paid_at'];
    protected $casts = ['amount'=>'decimal:2','paid_at'=>'datetime'];
    public function payable() { return $this->morphTo(); }
    public function customer() { return $this->belongsTo(Customer::class); }
}
