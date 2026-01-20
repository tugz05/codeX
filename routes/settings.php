<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/settings/profile');
    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    Route::get('settings/appearance', function () {
        $type = strtolower((string) optional(Auth::user())->account_type);
    // Map account types to their specific Inertia pages
    $componentMap = [
        'student'    => 'settings/Student/Appearance',
        'instructor' => 'settings/Instructor/Appearance',
        'admin'      => 'settings/Admin/Appearance',   // optional, in case you have one
    ];

    $component = $componentMap[$type] ?? 'settings/Appearance'; // fallback

    return Inertia::render($component, [
        // pass anything both pages need (optional)
        'user' => Auth::user(),
    ]);
    })->name('appearance');
});
