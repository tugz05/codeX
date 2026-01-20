<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        $request->session()->regenerate();
        // dd(Auth::user()->account_type);
        $account_type = Auth::user()->account_type;
        
        // Check if there's a class code in the request (from invite link)
        $code = $request->input('code') ?: $request->query('code');
        
        if($account_type === 'admin'){
            return redirect()->intended(route('admin.dashboard', absolute: false));
        }
        if($account_type === 'instructor'){
            return redirect()->intended(route('instructor.classlist', absolute: false));
        }
        if($account_type === 'student'){
            // If there's a code parameter, redirect to classlist with code to auto-open join dialog
            if ($code) {
                return redirect()->route('student.classlist', ['code' => $code])
                    ->with('success', 'Welcome back! You can now join the class.');
            }
            return redirect()->intended(route('student.dashboard', absolute: false));
        }

    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
