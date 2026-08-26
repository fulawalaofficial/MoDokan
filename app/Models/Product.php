<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Product extends Model
{
    protected $fillable = ['shop_id','product_category_id','supplier_id','name','sku','barcode','image','purchase_price','selling_price','quantity','unit_type','opening_stock','low_stock_alert','status'];
    protected $casts = ['purchase_price'=>'decimal:2','selling_price'=>'decimal:2','quantity'=>'decimal:2','opening_stock'=>'decimal:2','low_stock_alert'=>'decimal:2'];
    public function category() { return $this->belongsTo(ProductCategory::class, 'product_category_id'); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function stockHistories() { return $this->hasMany(StockHistory::class); }
    public function isLowStock(): bool { return (float)$this->quantity <= (float)$this->low_stock_alert; }
}
