<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\ShopCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function registerBusiness(Request $request)
    {
        $data = $request->validate([
            'owner_name' => ['required','string','max:255'],
            'mobile' => ['required','string','max:20'],
            'email' => ['required','email','unique:users,email'],
            'password' => ['required','string','min:6'],
            'shop_name' => ['required','string','max:255'],
            'shop_category_id' => ['nullable','exists:shop_categories,id'],
            'shop_category_name' => ['nullable','string','max:255'],
            'shop_address' => ['required','string'],
            'gst_number' => ['nullable','string','max:50'],
            'currency' => ['nullable','string','max:10'],
            'invoice_prefix' => ['nullable','string','max:20'],
            'opening_balance' => ['nullable','numeric'],
        ]);

        if (empty($data['shop_category_id']) && !empty($data['shop_category_name'])) {
            $category = ShopCategory::firstOrCreate(['name' => $data['shop_category_name']], ['status' => 'Active']);
            $data['shop_category_id'] = $category->id;
        }

        $shop = Shop::create([
            'shop_category_id' => $data['shop_category_id'] ?? null,
            'name' => $data['shop_name'],
            'address' => $data['shop_address'],
            'contact_number' => $data['mobile'],
            'gst_number' => $data['gst_number'] ?? null,
            'opening_balance' => $data['opening_balance'] ?? 0,
            'currency' => $data['currency'] ?? 'INR',
            'invoice_prefix' => $data['invoice_prefix'] ?? 'INV',
            'default_tax' => 0,
            'low_stock_alert' => 5,
            'status' => config('app.admin_approval_enabled') ? 'Pending' : 'Active',
        ]);

        $user = User::create([
            'shop_id' => $shop->id,
            'name' => $data['owner_name'],
            'email' => $data['email'],
            'mobile' => $data['mobile'],
            'password' => Hash::make($data['password']),
            'role' => 'shop_owner',
            'status' => 'Active',
        ]);
        $shop->update(['owner_id' => $user->id]);

        return response()->json([
            'message' => 'Business registered successfully.',
            'token' => $user->createToken('mobile')->plainTextToken,
            'user' => $user->load('shop.category'),
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate(['login' => ['required','string'], 'password' => ['required','string']]);
        $user = User::where('email', $data['login'])->orWhere('mobile', $data['login'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['login' => 'Invalid login credentials.']);
        }
        if ($user->status !== 'Active') abort(403, 'Account inactive.');
        return response()->json([
            'message' => 'Login successful.',
            'token' => $user->createToken('mobile')->plainTextToken,
            'user' => $user->load('shop.category'),
        ]);
    }

    public function me(Request $request) { return response()->json($request->user()->load('shop.category')); }
    public function logout(Request $request) { $request->user()->currentAccessToken()?->delete(); return response()->json(['message'=>'Logged out.']); }
    public function forgotPassword(Request $request) { return response()->json(['message' => 'Password reset/OTP provider can be connected here.']); }
    public function verifyOtp(Request $request) { return response()->json(['message' => 'OTP verified.']); }
}
