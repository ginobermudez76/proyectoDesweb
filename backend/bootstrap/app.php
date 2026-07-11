<?php

use App\Http\Middleware\CheckRolePermission;
use App\Shared\HttpStatus\HttpStatus;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->alias([
            'rbac' => CheckRolePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                // 1. Excepción de Validación (422 Unprocessable Entity)
                if ($e instanceof ValidationException) {
                    return response()->json([
                        'status' => 'error',
                        'message' => HttpStatus::UNPROCESSABLE_ENTITY->label(),
                        'description' => HttpStatus::UNPROCESSABLE_ENTITY->description(),
                        'details' => $e->errors(),
                    ], 422);
                }

                // 2. Excepción de Autenticación (401 Unauthorized)
                if ($e instanceof AuthenticationException) {
                    return response()->json([
                        'status' => 'error',
                        'message' => HttpStatus::UNAUTHORIZED->label(),
                        'description' => HttpStatus::UNAUTHORIZED->description(),
                    ], 401);
                }

                // 3. Excepción de Autorización / RBAC (403 Forbidden)
                if ($e instanceof AuthorizationException || $e instanceof AccessDeniedHttpException) {
                    return response()->json([
                        'status' => 'error',
                        'message' => HttpStatus::FORBIDDEN->label(),
                        'description' => HttpStatus::FORBIDDEN->description(),
                    ], 403);
                }

                // 4. Recurso No Encontrado (404 Not Found)
                if ($e instanceof NotFoundHttpException) {
                    return response()->json([
                        'status' => 'error',
                        'message' => HttpStatus::NOT_FOUND->label(),
                        'description' => HttpStatus::NOT_FOUND->description(),
                    ], 404);
                }

                // 5. Método No Soportado (405 Method Not Allowed)
                if ($e instanceof MethodNotAllowedHttpException) {
                    return response()->json([
                        'status' => 'error',
                        'message' => HttpStatus::METHOD_NOT_ALLOWED->label(),
                        'description' => HttpStatus::METHOD_NOT_ALLOWED->description(),
                    ], 405);
                }

                // 6. Límite de Peticiones (429 Too Many Requests)
                if ($e instanceof ThrottleRequestsException) {
                    return response()->json([
                        'status' => 'error',
                        'message' => HttpStatus::TOO_MANY_REQUESTS->label(),
                        'description' => HttpStatus::TOO_MANY_REQUESTS->description(),
                    ], 429);
                }

                // 7. Errores de Servidor u otros códigos HTTP
                $statusCode = 500;
                if (method_exists($e, 'getStatusCode')) {
                    $statusCode = $e->getStatusCode();
                }

                if (!config('app.debug')) {
                    $httpStatus = HttpStatus::tryFrom($statusCode) ?: HttpStatus::INTERNAL_SERVER_ERROR;

                    return response()->json([
                        'status' => 'error',
                        'message' => $httpStatus->label(),
                        'description' => $httpStatus->description(),
                    ], $statusCode);
                }
            }

            return null; // Dejar que Laravel procese la excepción si no es de API o está en modo debug
        });
    })->create();
