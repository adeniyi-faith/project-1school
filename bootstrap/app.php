<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\ActAsSchool::class,
            \App\Http\Middleware\DemoReadOnly::class,
        ]);

        // GPS devices can't send a CSRF token; they use their own secret instead
        $middleware->preventRequestForgery(except: ['webhooks/*']);

        $middleware->alias([
            'role'       => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // "Not allowed" shows as a plain page inside the app instead of Inertia's blank white pop-up (which
        // is what it draws for any error page it cannot read). The 403 status is kept, so nothing else changes.
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            $denied = $e instanceof \Illuminate\Auth\Access\AuthorizationException
                || ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface && $e->getStatusCode() === 403);

            if (! $denied || $request->expectsJson() && ! $request->header('X-Inertia') || ! $request->user()) {
                return null;
            }

            return \Inertia\Inertia::render('Error', ['message' => 'You do not have permission to do that.'])
                ->toResponse($request)->setStatusCode(403);
        });
    })->create();
