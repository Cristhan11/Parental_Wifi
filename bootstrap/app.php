<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/**
 * Bootstrap Application Configuration
 *
 * This file configures the Laravel application:
 * - Routes (where URLs are defined)
 * - Middleware (code that runs before/after requests)
 * - Exception handling (how errors are handled)
 *
 * This is one of the first files Laravel loads when starting the application
 */
return Application::configure(basePath: dirname(__DIR__))
    // Configure routing - tells Laravel where to find route files
    ->withRouting(
        web: __DIR__.'/../routes/web.php',        // Main web routes (GET, POST, etc.)
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php', // Artisan command routes
        health: '/up',                              // Health check endpoint
    )
    // Configure middleware - code that runs before requests reach controllers
    ->withMiddleware(function (Middleware $middleware): void {
        // Register custom role-based middleware with aliases
        // This allows us to use short names like 'role.parent' instead of full class names
        //
        // How it works:
        // - 'role.parent' is the alias (short name we use in routes)
        // - \App\Http\Middleware\EnsureUserIsParent::class is the actual middleware class
        //
        // Usage in routes:
        // Route::get('/devices', ...)->middleware('role.parent');
        // This will run EnsureUserIsParent middleware before the route handler
        $middleware->alias([
            'role.parent' => \App\Http\Middleware\EnsureUserIsParent::class,
            'role.admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'parent.dashboard' => \App\Http\Middleware\EnsureParentDashboardAccess::class,
            'audit.sensitive' => \App\Http\Middleware\AuditSensitiveAction::class,
        ]);
    })
    // Configure exception handling - how errors are displayed/logged
    ->withExceptions(function (Exceptions $exceptions): void {
        // Custom exception handling can be added here if needed
        // For now, using Laravel's default exception handling
    })->create();
