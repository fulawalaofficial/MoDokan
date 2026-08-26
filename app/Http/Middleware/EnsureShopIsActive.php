<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureShopIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || $user->status !== 'Active') {
            return response()->json(['message' => 'Account is inactive.'], 403);
        }

        if ($user->shop && $user->shop->status !== 'Active') {
            return response()->json(['message' => 'Shop account is inactive.'], 403);
        }

        return $next($request);
    }
}
