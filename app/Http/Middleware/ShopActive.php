<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShopActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Super admin is not tied to one shop.
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (!$user->shop) {
            return response()->json([
                'success' => false,
                'message' => 'No shop is linked with this user.',
            ], 403);
        }

        if ($user->shop->status !== 'Active') {
            return response()->json([
                'success' => false,
                'message' => $user->shop->status === 'Pending'
                    ? 'Your shop is waiting for admin approval.'
                    : 'Your shop is inactive.',
                'shop_status' => $user->shop->status,
            ], 403);
        }

        return $next($request);
    }
}
