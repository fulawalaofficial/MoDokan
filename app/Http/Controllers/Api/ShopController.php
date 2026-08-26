<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\ResolvesShop;
use Illuminate\Http\Request;
class ShopController extends Controller
{
    use ResolvesShop;
    public function show() { return response()->json($this->shop()->load('category','owner')); }
    public function update(Request $request) {
        $data = $request->validate([
            'name'=>['sometimes','string'], 'address'=>['sometimes','string'], 'contact_number'=>['sometimes','string'],
            'gst_number'=>['nullable','string'], 'currency'=>['sometimes','string'], 'invoice_prefix'=>['sometimes','string'],
            'default_tax'=>['nullable','numeric'], 'low_stock_alert'=>['nullable','numeric'], 'settings'=>['nullable','array']
        ]);
        $shop = $this->shop(); $shop->update($data); return response()->json(['message'=>'Shop profile updated.', 'shop'=>$shop->fresh('category')]);
    }
}
