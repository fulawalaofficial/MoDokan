<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StockHistory extends Model
{
    protected $fillable = ['shop_id','product_id','user_id','type','quantity','before_quantity','after_quantity','note','reference_type','reference_id'];
    protected $casts = ['quantity'=>'decimal:2','before_quantity'=>'decimal:2','after_quantity'=>'decimal:2'];
    public function product() { return $this->belongsTo(Product::class); }
    public function user() { return $this->belongsTo(User::class); }
}
