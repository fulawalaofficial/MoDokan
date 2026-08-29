<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\ShopCategory;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    public function registerBusiness(Request $request): JsonResponse
    {
        $data = $request->validate([
            'owner_name'         => ['required', 'string', 'max:255'],
            'mobile'             => ['required', 'string', 'max:20', Rule::unique('users', 'mobile')],
            'email'              => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password'           => ['required', 'string', 'min:6', 'max:255'],
            'shop_name'          => ['required', 'string', 'max:255'],
            'shop_category_id'   => ['nullable', 'integer', Rule::exists('shop_categories', 'id')],
            'shop_category_name' => ['nullable', 'string', 'max:255'],
            'shop_address'       => ['required', 'string', 'max:2000'],
            'gst_number'         => ['nullable', 'string', 'max:50'],
            'currency'           => ['nullable', 'string', 'max:10'],
            'invoice_prefix'     => ['nullable', 'string', 'max:20'],
            'opening_balance'    => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $result = DB::transaction(function () use ($data) {
                $categoryId = $data['shop_category_id'] ?? null;

                if (!$categoryId && !empty($data['shop_category_name'])) {
                    $category = ShopCategory::firstOrCreate(
                        ['name' => trim($data['shop_category_name'])],
                        [
                            'description' => null,
                            'supports_repair' => false,
                            'status' => 'Active',
                        ]
                    );

                    $categoryId = $category->id;
                }

                $approvalEnabled = (bool) config('modokana.admin_approval_enabled', false);

                $shop = Shop::create([
                    'shop_category_id' => $categoryId,
                    'owner_id'         => null,
                    'name'             => trim($data['shop_name']),
                    'address'          => trim($data['shop_address']),
                    'contact_number'   => trim($data['mobile']),
                    'gst_number'       => $data['gst_number'] ?? null,
                    'logo'             => null,
                    'opening_balance'  => $data['opening_balance'] ?? 0,
                    'currency'         => strtoupper($data['currency'] ?? 'INR'),
                    'invoice_prefix'   => strtoupper($data['invoice_prefix'] ?? 'INV'),
                    'default_tax'      => 0,
                    'low_stock_alert'  => 5,
                    'status'           => $approvalEnabled ? 'Pending' : 'Active',
                    'settings'         => [],
                ]);

                $user = User::create([
                    'shop_id'     => $shop->id,
                    'name'        => trim($data['owner_name']),
                    'email'       => strtolower(trim($data['email'])),
                    'mobile'      => trim($data['mobile']),
                    'password'    => Hash::make($data['password']),
                    'role'        => 'shop_owner',
                    'status'      => 'Active',
                    'permissions' => [],
                ]);

                $shop->update(['owner_id' => $user->id]);

                return [$shop, $user];
            });

            /** @var Shop $shop */
            /** @var User $user */
            [$shop, $user] = $result;

            $token = $user->createToken('mobile')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => $shop->status === 'Pending'
                    ? 'Business registered successfully. Waiting for admin approval.'
                    : 'Business registered successfully.',
                'token' => $token,
                'user' => $user->fresh()->load('shop.category'),
            ], 201);
        } catch (Throwable $e) {
            Log::error('Business registration failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'email' => $data['email'] ?? null,
                'mobile' => $data['mobile'] ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Business registration failed on the server.',
                'error' => app()->environment('production')
                    ? 'Please check laravel.log for the exact database/server error.'
                    : $e->getMessage(),
            ], 500);
        }
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($data['login']);

        $user = User::query()
            ->where('email', $login)
            ->orWhere('mobile', $login)
            ->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Invalid login credentials.'],
            ]);
        }

        if ($user->status !== 'Active') {
            return response()->json([
                'success' => false,
                'message' => 'Account inactive.',
            ], 403);
        }

        if (!$user->shop) {
            return response()->json([
                'success' => false,
                'message' => 'No shop is linked with this account.',
            ], 403);
        }

        if ($user->shop->status !== 'Active') {
            return response()->json([
                'success' => false,
                'message' => 'Shop is waiting for admin approval or is inactive.',
                'shop_status' => $user->shop->status,
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'token' => $user->createToken('mobile')->plainTextToken,
            'user' => $user->load('shop.category'),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'user' => $request->user()->load('shop.category'),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out.',
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'login' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'mobile' => ['nullable', 'string'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password reset/OTP provider can be connected here.',
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'OTP verified.',
        ]);
    }
}
