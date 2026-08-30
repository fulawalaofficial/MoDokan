<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountProfileController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],
            'mobile' => [
                'nullable',
                'digits:10',
                Rule::unique('users', 'mobile')->ignore($user->id),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ]);

        $user->name = trim($validated['name']);
        $user->mobile = !empty($validated['mobile'])
            ? $validated['mobile']
            : null;
        $user->email = !empty($validated['email'])
            ? strtolower(trim($validated['email']))
            : null;
        $user->save();

        $freshUser = $user->fresh();

        // The Mo Dokana app already reads shop data from user.shop.
        // Load it when the relationship exists in your User model.
        if (method_exists($freshUser, 'shop')) {
            $freshUser->load('shop');
        }

        return response()->json([
            'status' => true,
            'message' => 'Account details updated successfully.',
            'data' => [
                'user' => $freshUser,
            ],
        ]);
    }
}
