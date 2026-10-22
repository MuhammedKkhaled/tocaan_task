<?php

use App\Models\User;

it('returns the error envelope after exceeding the API rate limit', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user, 'api');

    foreach (range(1, 10) as $attempt) {
        $response = $this->getJson('/api/orders');
        $response->assertOk();
        expect($response)->toBeSuccessEnvelope();
    }

    $limited = $this->getJson('/api/orders');
    $limited->assertTooManyRequests()->assertJsonPath('message', 'Too many requests. Please slow down.');
    expect($limited)->toBeErrorEnvelope();
});
