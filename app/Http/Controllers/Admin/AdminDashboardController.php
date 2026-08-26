<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\User;
class AdminDashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'totalShops' => Shop::count(),
            'activeShops' => Shop::where('status','Active')->count(),
            'inactiveShops' => Shop::where('status','Inactive')->count(),
            'owners' => User::where('role','shop_owner')->count(),
        ]);
    }
}
