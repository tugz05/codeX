<?php


use \App\Http\Middleware\StudentMiddleware;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\InstructorMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(__DIR__.'/../routes/channels.php')
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function ($response, $exception, $request) {
            // Handle authentication exceptions for Inertia requests
            if ($exception instanceof \Illuminate\Auth\AuthenticationException) {
                if ($request->header('X-Inertia')) {
                    // For Inertia requests, return a 409 response to force a client-side reload
                    return response()->json([
                        'message' => 'Your session has expired. Please log in again.',
                    ], 409)->header('X-Inertia-Location', route('login'));
                }
            }
            
            // Handle 419 CSRF token mismatch for Inertia
            if ($exception instanceof \Illuminate\Session\TokenMismatchException && $request->header('X-Inertia')) {
                return response()->json([
                    'message' => 'Page expired. Please refresh and try again.',
                ], 419)->header('X-Inertia-Location', $request->url());
            }
            
            return $response;
        });
    })->create();
