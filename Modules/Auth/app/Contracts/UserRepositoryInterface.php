<?php

namespace Modules\Auth\Contracts;

use App\Models\User;
use Modules\Core\Contracts\BaseRepositoryInterface;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): User;
}
