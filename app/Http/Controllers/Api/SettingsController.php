<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\ResolvesShop;
use Illuminate\Http\Request;
class SettingsController extends Controller
{
    use ResolvesShop;
    public function update(Request $request) {
        $data = $request->validate(['settings'=>['required','array']]);
        $shop = $this->shop();
        $shop->settings = array_merge($shop->settings ?? [], $data['settings']);
        $shop->save();
        return response()->json(['message'=>'Settings updated.', 'settings'=>$shop->settings]);
    }
}
