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
        // Never interfere with auth routes (login, register, etc.) - check by route name or path
        $routeName = $request->route()?->getName();
        $path = $request->path();

        if ($routeName && (str_starts_with($routeName, 'login') || str_starts_with($routeName, 'register') || str_starts_with($routeName, 'password.') || str_starts_with($routeName, 'google.'))) {
            return $next($request);
        }

        if ($path === 'login' || $path === 'register' || str_starts_with($path, 'auth/') || str_starts_with($path, 'forgot-password') || str_starts_with($path, 'reset-password')) {
            return $next($request);
        }

        $user = $request->user();

        // Handle /student/classlist route
        if ($request->is('student/classlist')) {
            // If user is not authenticated and has a code parameter, redirect to login with code preserved
            if (!$user && $request->has('code')) {
                return redirect()->route('login', ['code' => $request->query('code')])
                    ->with('message', 'Please log in to join the class.');
            }

            // If authenticated as student, allow access (with or without code)
            if ($user && $user->account_type === 'student') {
                return $next($request);
            }

            // If authenticated but not a student and has code, allow to view (they'll see message to log in as student)
            if ($user && $user->account_type !== 'student' && $request->has('code')) {
                return $next($request);
            }

            // If no code and not authenticated student, fall through to normal check
        }

        // For join route, redirect unauthenticated users to login instead of aborting
        if ($request->is('student/class-join') && !$user) {
            return redirect()->route('login')->with('error', 'Please log in to join the class.');
        }

        // If not authenticated, redirect to login
        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Please log in to access this page.');
        }

        // If authenticated but not a student, redirect to login with message
        if ($user->account_type !== 'student') {
            return redirect()->route('login')
                ->with('error', 'Please log in with a student account to access this page.');
        }

        return $next($request);
    }
}
