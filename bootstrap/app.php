<?php

use App\Helpers\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // $middleware->appendToGroup('api', \Rakutentech\LaravelRequestDocs\LaravelRequestDocsMiddleware::class);
        // $middleware->validateCsrfTokens([
        //     'payment/*',
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : ($e->getCode() ?: 500);

                $message = $e->getMessage();
                if ($message == 'Unauthenticated.') $status = 401;

                if (!in_array($status, array_keys(Response::$statusTexts))) {
                    $status = 500;
                }

                return ApiResponse::error($message, $status);
            }

            return null;
        });
    })->create();
