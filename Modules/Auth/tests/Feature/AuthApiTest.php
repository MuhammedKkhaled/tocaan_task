<?php

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

function stageNineBearer(string $token): array
{
    return ['Authorization' => "Bearer {$token}"];
}

function stageNineTokenFor(User $user): string
{
    return auth('api')->login($user);
}

it('registers a user and returns a token', function (): void {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Test User',
        'email' => 'TEST@EXAMPLE.COM',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.token_type', 'bearer')
        ->assertJsonPath('data.user.email', 'test@example.com');
    expect($response)->toBeSuccessEnvelope();

    $user = User::query()->where('email', 'test@example.com')->firstOrFail();
    expect(Hash::check('password', $user->password))->toBeTrue();
});

it('validates registration input', function (array $payload, string $errorKey): void {
    $response = $this->postJson('/api/auth/register', $payload);

    $response->assertUnprocessable()->assertJsonValidationErrors($errorKey, 'error');
    expect($response)->toBeErrorEnvelope();
})->with([
    'name is required' => [[
        'email' => 'user@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ], 'name'],
    'email must be valid' => [[
        'name' => 'User',
        'email' => 'invalid',
        'password' => 'password',
        'password_confirmation' => 'password',
    ], 'email'],
    'password must be confirmed' => [[
        'name' => 'User',
        'email' => 'user@example.com',
        'password' => 'password',
        'password_confirmation' => 'different',
    ], 'password'],
]);

it('logs in with valid credentials and rejects invalid credentials', function (): void {
    $user = User::factory()->create(['email' => 'user@example.com']);

    $success = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);
    $success->assertOk()->assertJsonPath('data.user.id', $user->id);
    expect($success)->toBeSuccessEnvelope();

    $failure = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);
    $failure->assertUnauthorized()->assertJsonPath('message', 'Invalid email or password.');
    expect($failure)->toBeErrorEnvelope();
});

it('retrieves the authenticated user', function (): void {
    $user = User::factory()->create();
    $response = $this->withHeaders(stageNineBearer(stageNineTokenFor($user)))
        ->getJson('/api/auth/me');

    $response->assertOk()->assertJsonPath('data.id', $user->id);
    expect($response)->toBeSuccessEnvelope();
});

it('refreshes a bearer token', function (): void {
    $user = User::factory()->create();
    $token = stageNineTokenFor($user);
    $response = $this->withHeaders(stageNineBearer($token))->postJson('/api/auth/refresh');

    $response->assertOk()->assertJsonPath('data.user.id', $user->id);
    expect($response)->toBeSuccessEnvelope()
        ->and($response->json('data.access_token'))->not->toBe($token);
});

it('invalidates the token on logout', function (): void {
    $user = User::factory()->create();
    $headers = stageNineBearer(stageNineTokenFor($user));

    $logout = $this->withHeaders($headers)->postJson('/api/auth/logout');
    $logout->assertOk();
    expect($logout)->toBeSuccessEnvelope();

    $reused = $this->withHeaders($headers)->getJson('/api/auth/me');
    $reused->assertUnauthorized();
    expect($reused)->toBeErrorEnvelope();
});

it('returns the error envelope for invalid and expired tokens', function (): void {
    $invalid = $this->withHeaders(stageNineBearer('not-a-token'))->getJson('/api/auth/me');
    $invalid->assertUnauthorized();
    expect($invalid)->toBeErrorEnvelope();

    $user = User::factory()->create();
    $now = Carbon::now();
    Carbon::setTestNow($now->copy()->subHours(2));
    $expiredToken = stageNineTokenFor($user);
    Carbon::setTestNow($now);
    auth('api')->forgetUser();

    $expired = $this->withHeaders(stageNineBearer($expiredToken))->getJson('/api/auth/me');
    $expired->assertUnauthorized();
    expect($expired)->toBeErrorEnvelope();
});
