<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

expect()->extend('toBeSuccessEnvelope', function () {
    expect($this->value)->toBeInstanceOf(TestResponse::class);

    $this->value
        ->assertJsonStructure(['message'])
        ->assertJsonMissingPath('error');

    return $this;
});

expect()->extend('toBeErrorEnvelope', function () {
    expect($this->value)->toBeInstanceOf(TestResponse::class);

    $this->value->assertJsonStructure(['message', 'error']);

    return $this;
});

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', '../Modules/*/tests/Feature');

pest()->extend(TestCase::class)
    ->in('Unit', '../Modules/*/tests/Unit');
