<?php

/**
 * * BOOTSTRAP: Laravel 12 application configuration
 * * This file replaces the old Kernel.php files
 */

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Sentry\Laravel\Integration;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',  // * Health check endpoint for monitoring
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // * Register middleware aliases used in routes
        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuthentication::class,
            'teacher.auth' => \App\Http\Middleware\TeacherAuthentication::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // * Sentry error tracking integration
        Integration::handles($exceptions);

        // * Return JSON for AJAX 404 errors
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Resource not found'], 404);
            }
        });

        // * Handle CSRF token mismatch with friendly message
        $exceptions->render(function (Illuminate\Session\TokenMismatchException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Your session has expired. Please refresh the page and try again.',
                ], 419);
            }

            return back()
                ->withInput()
                ->withErrors(['csrf' => 'Your session has expired. Please try again.']);
        });
    })->create();
