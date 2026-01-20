<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StudentMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->account_type !== 'student') {
            // Option 1: abort with 403
            abort(403, 'Unauthorized action. Only students can access this page.');
            // Option 2: redirect (uncomment below if you prefer redirect)
            // return redirect()->route('login')->with('error', 'Please login as student.');
        }
        return $next($request);
    }
}
