<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Supplier extends Model
{
    protected $fillable = ['shop_id','name','mobile','address','product_supplied','total_purchase','paid_amount','due_amount','status'];
    protected $casts = ['total_purchase'=>'decimal:2','paid_amount'=>'decimal:2','due_amount'=>'decimal:2'];
    public function products() { return $this->hasMany(Product::class); }
}
