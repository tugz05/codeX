<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
{
    $data = $request->validate([
        'name'         => ['required', 'string', 'max:255'],
        'email'        => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email', 'ends_with:@nemsu.edu.ph'],
        'password'     => ['required', 'confirmed', Rules\Password::defaults()],
        // require and constrain the role
        'account_type' => ['required', 'in:instructor,student'],
    ], [
        'email.ends_with' => 'Email must be from @nemsu.edu.ph domain.',
    ]);

    $user = User::create([
        'name'         => $data['name'],
        'email'        => $data['email'],
        'password'     => Hash::make($data['password']),
        'account_type' => $data['account_type'],
    ]);

    event(new Registered($user));

    Auth::login($user);
    $request->session()->regenerate();

    // Redirect based on role (same behavior as your LoginRequest version)
    return match ($user->account_type) {
        'instructor' => to_route('instructor.classlist'),
        'student'    => to_route('student.dashboard'),
        default      => to_route('user'), // fallback (optional)
    };
}
}
