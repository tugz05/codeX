<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordController extends Controller
{
    /**
     * Show the user's password settings page.
     */
    public function edit(): Response
    {
        $type = strtolower((string) optional(Auth::user())->account_type);
    // Map account types to their specific Inertia pages
    $componentMap = [
        'student'    => 'settings/Student/Password',
        'instructor' => 'settings/Instructor/Password',
        'admin'      => 'settings/Admin/Password',   // optional, in case you have one
    ];

    $component = $componentMap[$type] ?? 'settings/Profile'; // fallback

        return Inertia::render($component);
    }

    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back();
    }
}
