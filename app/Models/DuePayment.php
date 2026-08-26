<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DuePayment extends Model
{
    protected $fillable = ['shop_id','customer_id','sale_id','repair_id','amount','paid_amount','remaining_amount','reminder_date','status','note'];
    protected $casts = ['amount'=>'decimal:2','paid_amount'=>'decimal:2','remaining_amount'=>'decimal:2','reminder_date'=>'date'];
}
