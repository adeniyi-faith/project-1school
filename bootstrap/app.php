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
            \App\Http\Middleware\BlockSuspendedSchool::class,
            \App\Http\Middleware\EnsureAccountActive::class,
            \App\Http\Middleware\AuditPlatformActions::class,
            \App\Http\Middleware\ActAsSchool::class,
            \App\Http\Middleware\DemoReadOnly::class,
        ]);

        // GPS devices can't send a CSRF token; they use their own secret instead
        $middleware->preventRequestForgery(except: ['webhooks/*', 's/*/newsletter/unsubscribe/*']);

        $middleware->alias([
            'role'       => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'platform'   => \App\Http\Middleware\EnsurePlatformAccess::class,
            'school.token' => \App\Http\Middleware\AuthenticateSchoolToken::class,
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

        // Any other failure inside the app (a save that breaks, a page left open too long, a file that is too big)
        // used to show Inertia's blank white pop-up with the raw error page in it. Now a save goes back to the
        // form with a plain message, and a page that cannot open shows a plain page. The error is still logged.
        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response, \Throwable $e, \Illuminate\Http\Request $request) {
            if (! $request->header('X-Inertia') || ! $request->hasSession() || $response->getStatusCode() < 400 || $response->getStatusCode() === 403
                || $response->getStatusCode() === 422 || $response->getStatusCode() === 409 || $response->headers->has('X-Inertia')) {
                return $response;
            }

            $status = $response->getStatusCode();
            $message = match (true) {
                $status === 419 => 'This page was open for a long time, so we could not save. Please try again.',
                $status === 413 => 'That file is too big to upload. Please choose a smaller one.',
                $status === 404 => 'We could not find that. It may have been deleted.',
                $status === 429 => 'Too many tries in a short time. Please wait a minute and try again.',
                $status === 503 => 'The system is being updated. Please try again in a few minutes.',
                default => 'Something went wrong and this was not saved. Please try again. If it keeps happening, tell support.',
            };

            // A short code (the kind of error, plus the database error number if there is one) so support can tell what broke
            $code = class_basename($e).($e instanceof \Illuminate\Database\QueryException ? ' '.($e->errorInfo[0] ?? '').'/'.($e->errorInfo[1] ?? '') : '');
            if ($status >= 500) {
                $message .= " (Support code: {$code})";
            }

            if (! $request->isMethod('GET')) {
                // 303 so the browser asks for the page again with GET; a plain 302 would repeat the PUT and fail
                return back(303)->with('error', $message);
            }

            return \Inertia\Inertia::render('Error', ['message' => $message, 'title' => $status === 404 ? 'Not found' : 'Something went wrong', 'kind' => 'error'])
                ->toResponse($request)->setStatusCode($status);
        });
    })->create();
