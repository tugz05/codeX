<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect to Google OAuth
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function callback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $email = strtolower($googleUser->getEmail());

            // Validate that Google email is from @nemsu.edu.ph domain
            if (!str_ends_with($email, '@nemsu.edu.ph')) {
                return redirect()->route('login')
                    ->withErrors(['email' => 'Only @nemsu.edu.ph email addresses are allowed for Google sign-in.']);
            }

            // Check if user exists by email
            $user = User::where('email', $email)->first();

            if ($user) {
                // User exists (manual registration), link Google account
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'name' => $googleUser->getName(), // Update name from Google
                    'avatar' => $googleUser->getAvatar(), // Update avatar from Google
                ]);
                
                Auth::login($user);
            } else {
                // Create new user from Google OAuth
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $email,
                    'password' => bcrypt(uniqid()), // Random password since OAuth
                    'account_type' => 'student', // Default to student
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                ]);

                Auth::login($user);
            }

            $request->session()->regenerate();

            // Redirect based on account type
            $account_type = $user->account_type;
            if ($account_type === 'instructor') {
                return redirect()->intended(route('instructor.classlist', absolute: false));
            }
            
            return redirect()->intended(route('student.dashboard', absolute: false));
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Unable to login with Google. Please try again.']);
        }
    }
}
