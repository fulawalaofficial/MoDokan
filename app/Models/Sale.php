<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Sale extends Model
{
    protected $fillable = ['shop_id','customer_id','user_id','invoice_no','subtotal','discount','tax','total_amount','paid_amount','due_amount','payment_method','payment_status','sale_status','sale_date','notes'];
    protected $casts = ['subtotal'=>'decimal:2','discount'=>'decimal:2','tax'=>'decimal:2','total_amount'=>'decimal:2','paid_amount'=>'decimal:2','due_amount'=>'decimal:2','sale_date'=>'datetime'];
    public function customer() { return $this->belongsTo(Customer::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function items() { return $this->hasMany(SaleItem::class); }
    public function payments() { return $this->morphMany(Payment::class, 'payable'); }
}
