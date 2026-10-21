<?php

namespace Modules\Auth\Repositories;

use App\Models\User;
use Modules\Auth\Contracts\UserRepositoryInterface;
use Modules\Core\Repositories\BaseRepository;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $user)
    {
        parent::__construct($user);
    }

    public function create(array $attributes): User
    {
        /** @var User $user */
        $user = parent::create($attributes);

        return $user;
    }
}
