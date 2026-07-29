<?php

use App\Http\Middleware\EnsureNoActiveAssessmentElsewhere;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'assessment.guard' => EnsureNoActiveAssessmentElsewhere::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            $message = 'Sesi halaman sudah diperbarui. Silakan coba lagi dari halaman login atau refresh halaman.';

            if ($request->expectsJson()) {
                return response()->json([
                    'csrf_expired' => true,
                    'message' => $message,
                    'redirect' => route('login'),
                ]);
            }

            return redirect()
                ->route('login')
                ->with('warning', $message);
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            $message = 'Sesi halaman sudah diperbarui. Silakan coba lagi dari halaman login atau refresh halaman.';

            if ($request->expectsJson()) {
                return response()->json([
                    'csrf_expired' => true,
                    'message' => $message,
                    'redirect' => route('login'),
                ]);
            }

            return redirect()
                ->route('login')
                ->with('warning', $message);
        });
    })->create();
