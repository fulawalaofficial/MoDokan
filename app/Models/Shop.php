<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $fillable = ['shop_category_id','owner_id','name','address','contact_number','gst_number','logo','opening_balance','currency','invoice_prefix','default_tax','low_stock_alert','status','settings'];
    protected $casts = ['opening_balance'=>'decimal:2','default_tax'=>'decimal:2','low_stock_alert'=>'integer','settings'=>'array'];
    public function category() { return $this->belongsTo(ShopCategory::class, 'shop_category_id'); }
    public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
    public function users() { return $this->hasMany(User::class); }
    public function products() { return $this->hasMany(Product::class); }
    public function customers() { return $this->hasMany(Customer::class); }
}
