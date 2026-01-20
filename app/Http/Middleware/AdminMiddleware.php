<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // If not authenticated, redirect to login
        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Please log in to access this page.');
        }

        // If authenticated but not an admin, redirect to login with message
        if ($user->account_type !== 'admin') {
            return redirect()->route('login')
                ->with('error', 'Please log in with an admin account to access this page.');
        }

        return $next($request);
    }
}
