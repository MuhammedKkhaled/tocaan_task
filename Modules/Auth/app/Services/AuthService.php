<?php

namespace Modules\Auth\Services;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use LogicException;
use Modules\Auth\Contracts\UserRepositoryInterface;
use Modules\Auth\Exceptions\InvalidCredentialsException;
use Tymon\JWTAuth\JWTGuard;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {
    }

    /**
     * @param  array{name: string, email: string, password: string}  $attributes
     * @return array{access_token: string, token_type: string, expires_in: int, user: User}
     */
    public function register(array $attributes): array
    {
        $user = DB::transaction(fn (): User => $this->users->create([
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'password' => Hash::make($attributes['password']),
        ]));

        return $this->tokenPayload($this->guard()->login($user), $user);
    }

    /**
     * @param  array{email: string, password: string}  $credentials
     * @return array{access_token: string, token_type: string, expires_in: int, user: User}
     */
    public function login(array $credentials): array
    {
        $guard = $this->guard();
        $token = $guard->attempt($credentials);

        if (! is_string($token)) {
            throw new InvalidCredentialsException();
        }

        return $this->tokenPayload($token, $this->user($guard));
    }

    public function logout(): void
    {
        $this->guard()->logout();
    }

    /** @return array{access_token: string, token_type: string, expires_in: int, user: User} */
    public function refresh(): array
    {
        $guard = $this->guard();
        $token = $guard->refresh();
        $guard->setToken($token);

        return $this->tokenPayload($token, $this->user($guard));
    }

    public function me(): User
    {
        return $this->user($this->guard());
    }

    private function guard(): JWTGuard
    {
        $guard = Auth::guard('api');

        if (! $guard instanceof JWTGuard) {
            throw new LogicException('The api guard must use the JWT driver.');
        }

        return $guard;
    }

    private function user(JWTGuard $guard): User
    {
        $user = $guard->user();

        if (! $user instanceof User) {
            throw new AuthenticationException();
        }

        return $user;
    }

    /** @return array{access_token: string, token_type: string, expires_in: int, user: User} */
    private function tokenPayload(string $token, User $user): array
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60,
            'user' => $user,
        ];
    }
}
