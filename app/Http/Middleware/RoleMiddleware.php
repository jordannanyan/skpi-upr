<?php

namespace App\Http\Middleware;

use Closure;

class RoleMiddleware
{
    public function handle($request, Closure $next, ...$roles)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message'=>'Unauthenticated'], 401);

        if (!in_array($user->role, $roles, true)) {
            return response()->json(['message'=>'Forbidden'], 403);
        }
        return $next($request);
    }
}
