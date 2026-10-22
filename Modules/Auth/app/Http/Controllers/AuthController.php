<?php

namespace Modules\Auth\Http\Controllers;

use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Http\Resources\TokenResource;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Auth\Services\AuthService;
use Modules\Core\Http\Controllers\BaseApiController;
use Modules\Core\Http\Documentation\ApiErrorResponse;
use Symfony\Component\HttpFoundation\Response;

#[Group('Authentication')]
class AuthController extends BaseApiController
{
    public function __construct(
        private readonly AuthService $auth,
    ) {
    }

    /** Register a new user and issue a bearer token. */
    #[ApiResponse(status: 201, description: 'Registration successful', type: 'array{message: string, data: array{access_token: string, token_type: string, expires_in: int, user: UserResource}}', examples: [['message' => 'Registration successful.', 'data' => []]])]
    #[ApiErrorResponse(422)]
    #[ApiErrorResponse(429)]
    public function register(RegisterRequest $request): JsonResponse
    {
        return $this->successResponse(
            'Registration successful.',
            ['data' => new TokenResource($this->auth->register($request->validated()))],
            Response::HTTP_CREATED,
        );
    }

    /** Authenticate a user and issue a bearer token. */
    #[ApiResponse(description: 'Login successful', type: 'array{message: string, data: array{access_token: string, token_type: string, expires_in: int, user: UserResource}}', examples: [['message' => 'Login successful.', 'data' => []]])]
    #[ApiErrorResponse(401)]
    #[ApiErrorResponse(422)]
    #[ApiErrorResponse(429)]
    public function login(LoginRequest $request): JsonResponse
    {
        return $this->successResponse(
            'Login successful.',
            ['data' => new TokenResource($this->auth->login($request->validated()))],
        );
    }

    /** Invalidate the current bearer token. */
    #[ApiResponse(description: 'Logout successful', type: 'array{message: string}', examples: [['message' => 'Logout successful.']])]
    #[ApiErrorResponse(401)]
    #[ApiErrorResponse(429)]
    public function logout(): JsonResponse
    {
        $this->auth->logout();

        return $this->successResponse('Logout successful.');
    }

    /** Refresh the current bearer token. */
    #[ApiResponse(description: 'Token refreshed', type: 'array{message: string, data: array{access_token: string, token_type: string, expires_in: int, user: UserResource}}', examples: [['message' => 'Token refreshed successfully.', 'data' => []]])]
    #[ApiErrorResponse(401)]
    #[ApiErrorResponse(429)]
    public function refresh(): JsonResponse
    {
        return $this->successResponse(
            'Token refreshed successfully.',
            ['data' => new TokenResource($this->auth->refresh())],
        );
    }

    /** Retrieve the authenticated user. */
    #[ApiResponse(description: 'Authenticated user', type: 'array{message: string, data: UserResource}', examples: [['message' => 'Authenticated user retrieved successfully.', 'data' => []]])]
    #[ApiErrorResponse(401)]
    #[ApiErrorResponse(429)]
    public function me(): JsonResponse
    {
        return $this->successResponse(
            'Authenticated user retrieved successfully.',
            ['data' => new UserResource($this->auth->me())],
        );
    }
}
