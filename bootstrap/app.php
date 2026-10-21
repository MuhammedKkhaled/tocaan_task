<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Core\Exceptions\BusinessRuleException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );

        $envelope = static fn (string $message, int $status, mixed $error = null): JsonResponse =>
            response()->json([
                'message' => $message,
                'error' => $error,
            ], $status);

        $exceptions->render(
            fn (BusinessRuleException $exception): JsonResponse =>
            $envelope($exception->getMessage(), $exception->status()),
        );

        $exceptions->render(function (ValidationException $exception) use ($envelope): JsonResponse {
            $message = collect($exception->errors())->flatten()->first()
                ?? 'The given data was invalid.';

            return $envelope($message, $exception->status, $exception->errors());
        });

        $exceptions->render(
            fn (NotFoundHttpException $exception): JsonResponse =>
            $envelope('The requested resource was not found.', 404),
        );

        $exceptions->render(
            fn (AuthenticationException $exception): JsonResponse =>
            $envelope('Unauthenticated. Please log in.', 401),
        );

        $exceptions->render(
            fn (AccessDeniedHttpException $exception): JsonResponse =>
            $envelope('You are not allowed to perform this action.', 403),
        );

        $exceptions->render(
            fn (ThrottleRequestsException $exception): JsonResponse =>
            $envelope('Too many requests. Please slow down.', 429),
        );

        $exceptions->render(
            fn (TokenExpiredException $exception): JsonResponse =>
            $envelope('Your token has expired.', 401),
        );

        $exceptions->render(
            fn (TokenInvalidException $exception): JsonResponse =>
            $envelope('Your token is invalid.', 401),
        );

        $exceptions->render(
            fn (JWTException $exception): JsonResponse =>
            $envelope('A valid token is required.', 401),
        );

        $exceptions->render(function (\Throwable $exception, Request $request) use ($envelope): ?JsonResponse {
            if (config('app.debug') || (! $request->is('api/*') && ! $request->expectsJson())) {
                return null;
            }

            return $envelope('An unexpected error occurred.', 500);
        });
    })->create();
