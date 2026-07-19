<?php

use App\Http\Middleware\CheckRolePermission;
use App\Shared\HttpStatus\HttpStatus;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

function apiExceptionResponse(HttpStatus $status, int $code, array $extra = []): JsonResponse
{
    return response()->json(array_merge([
        'status' => 'error',
        'message' => $status->label(),
        'description' => $status->description(),
    ], $extra), $code);
}

function renderApiException(Throwable $e, Request $request): ?JsonResponse
{
    if (!($request->is('api/*') || $request->expectsJson())) {
        return null;
    }

    if ($e instanceof ValidationException) {
        return apiExceptionResponse(HttpStatus::UNPROCESSABLE_ENTITY, 422, [
            'details' => $e->errors(),
        ]);
    }

    if ($e instanceof AuthenticationException) {
        return apiExceptionResponse(HttpStatus::UNAUTHORIZED, 401);
    }

    if ($e instanceof AuthorizationException || $e instanceof AccessDeniedHttpException) {
        return apiExceptionResponse(HttpStatus::FORBIDDEN, 403);
    }

    if ($e instanceof NotFoundHttpException) {
        return apiExceptionResponse(HttpStatus::NOT_FOUND, 404);
    }

    if ($e instanceof MethodNotAllowedHttpException) {
        return apiExceptionResponse(HttpStatus::METHOD_NOT_ALLOWED, 405);
    }

    if ($e instanceof ThrottleRequestsException) {
        return apiExceptionResponse(HttpStatus::TOO_MANY_REQUESTS, 429);
    }

    if (config('app.debug')) {
        return null;
    }

    $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
    $httpStatus = HttpStatus::tryFrom($statusCode) ?: HttpStatus::INTERNAL_SERVER_ERROR;

    return apiExceptionResponse($httpStatus, $statusCode);
}

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
        $exceptions->render(fn (Throwable $e, Request $request) => renderApiException($e, $request));
    })->create();
