<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SubscriptionPlan extends Model
{
    protected $fillable = ['name','price','duration_days','max_staff','max_products','features','status'];
    protected $casts = ['price'=>'decimal:2','features'=>'array'];
}
