<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Notification extends Model
{
    protected $fillable = ['shop_id','user_id','title','message','type','read_at','data'];
    protected $casts = ['read_at'=>'datetime','data'=>'array'];
}
