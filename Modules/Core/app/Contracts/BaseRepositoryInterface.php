<?php

namespace Modules\Core\Contracts;

use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryInterface
{
    public function find(int|string $id): ?Model;

    public function findOrFail(int|string $id): Model;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Model;

    /** @param array<string, mixed> $attributes */
    public function update(Model $model, array $attributes): Model;

    public function delete(Model $model): bool;
}
