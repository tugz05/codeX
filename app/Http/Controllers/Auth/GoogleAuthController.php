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
    public function redirect(Request $request)
    {
        // Store the code parameter in session if present (for class invite links)
        if ($request->has('code')) {
            $request->session()->put('invite_code', $request->query('code'));
        }
        
        return Socialite::driver('google')
            ->scopes(['profile', 'email'])
            ->redirect();
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

            // Get avatar URL from Google user
            $avatarUrl = $googleUser->getAvatar();
            // If getAvatar() returns null, try to get from raw data
            if (!$avatarUrl && isset($googleUser->user['picture'])) {
                $avatarUrl = $googleUser->user['picture'];
            }

            // Check if user exists by email
            $user = User::where('email', $email)->first();

            if ($user) {
                // User exists (manual registration), link Google account
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'name' => $googleUser->getName(), // Update name from Google
                    'avatar' => $avatarUrl, // Update avatar from Google
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
                    'avatar' => $avatarUrl,
                ]);

                Auth::login($user);
            }

            $request->session()->regenerate();

            // Check if there's a class code in session (from invite link)
            $code = $request->session()->pull('invite_code');

            // Redirect based on account type
            $account_type = $user->account_type;
            if ($account_type === 'instructor') {
                return redirect()->intended(route('instructor.classlist', absolute: false));
            }
            
            // If there's a code parameter, redirect to classlist with code to auto-open join dialog
            if ($code) {
                return redirect()->route('student.classlist', ['code' => $code])
                    ->with('success', 'Welcome back! You can now join the class.');
            }
            
            return redirect()->intended(route('student.dashboard', absolute: false));
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Unable to login with Google. Please try again.']);
        }
    }
}
