<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProductCategory extends Model
{
    protected $fillable = ['shop_id','name','description','status'];
    public function products() { return $this->hasMany(Product::class); }
}
