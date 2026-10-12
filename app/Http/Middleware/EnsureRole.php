<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if ($role === 'user' && $user?->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        abort_unless($user && $user->role->value === $role, 403);

        if ($role === 'user' && ! $user->client) {
            return redirect()->route('register');
        }

        return $next($request);
    }
}
