<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SaleItem extends Model
{
    protected $fillable = ['sale_id','product_id','product_name','quantity','purchase_price','selling_price','discount','total','profit'];
    protected $casts = ['quantity'=>'decimal:2','purchase_price'=>'decimal:2','selling_price'=>'decimal:2','discount'=>'decimal:2','total'=>'decimal:2','profit'=>'decimal:2'];
    public function sale() { return $this->belongsTo(Sale::class); }
    public function product() { return $this->belongsTo(Product::class); }
}
