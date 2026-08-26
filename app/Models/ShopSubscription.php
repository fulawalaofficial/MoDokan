<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ShopSubscription extends Model
{
    protected $fillable = ['shop_id','subscription_plan_id','start_date','end_date','amount','payment_status','status'];
    protected $casts = ['start_date'=>'date','end_date'=>'date','amount'=>'decimal:2'];
}
