<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', '../Modules/*/tests/Feature');

pest()->extend(TestCase::class)
    ->in('Unit', '../Modules/*/tests/Unit');
