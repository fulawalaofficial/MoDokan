<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Expense extends Model
{
    protected $fillable = ['shop_id','user_id','category','title','amount','expense_date','payment_method','note'];
    protected $casts = ['amount'=>'decimal:2','expense_date'=>'date'];
    public function user() { return $this->belongsTo(User::class); }
}
