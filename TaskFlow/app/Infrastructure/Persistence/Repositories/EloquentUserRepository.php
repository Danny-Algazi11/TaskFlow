<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Auth\Repositories\UserRepositoryInterface;
use App\Infrastructure\Persistence\Models\User;

/**
 * The only place in the codebase that knows the User Eloquent model exists.
 * Implements the Domain interface, so it's bound to it in a service
 * provider and everything above (Use Cases) stays ignorant of Eloquent.
 */
class EloquentUserRepository implements UserRepositoryInterface
{
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }
}
