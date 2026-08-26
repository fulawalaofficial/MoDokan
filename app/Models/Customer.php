<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Customer extends Model
{
    protected $fillable = ['shop_id','name','mobile','address','photo','notes','total_purchase','total_paid','total_due','last_payment_date','status'];
    protected $casts = ['total_purchase'=>'decimal:2','total_paid'=>'decimal:2','total_due'=>'decimal:2','last_payment_date'=>'date'];
    public function sales() { return $this->hasMany(Sale::class); }
    public function payments() { return $this->hasMany(Payment::class); }
    public function repairs() { return $this->hasMany(Repair::class); }
}
