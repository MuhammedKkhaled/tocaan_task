<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Http\Resources\TokenResource;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Auth\Services\AuthService;
use Modules\Core\Http\Controllers\BaseApiController;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends BaseApiController
{
    public function __construct(
        private readonly AuthService $auth,
    ) {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        return $this->successResponse(
            'Registration successful.',
            ['data' => new TokenResource($this->auth->register($request->validated()))],
            Response::HTTP_CREATED,
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        return $this->successResponse(
            'Login successful.',
            ['data' => new TokenResource($this->auth->login($request->validated()))],
        );
    }

    public function logout(): JsonResponse
    {
        $this->auth->logout();

        return $this->successResponse('Logout successful.');
    }

    public function refresh(): JsonResponse
    {
        return $this->successResponse(
            'Token refreshed successfully.',
            ['data' => new TokenResource($this->auth->refresh())],
        );
    }

    public function me(): JsonResponse
    {
        return $this->successResponse(
            'Authenticated user retrieved successfully.',
            ['data' => new UserResource($this->auth->me())],
        );
    }
}
