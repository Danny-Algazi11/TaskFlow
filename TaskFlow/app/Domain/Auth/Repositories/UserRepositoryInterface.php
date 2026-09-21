<?php

namespace App\Domain\Auth\Repositories;

use App\Infrastructure\Persistence\Models\User;

/**
 * Contract the Application layer codes against. It has no idea Eloquent
 * exists — that's the whole point: swap the Infrastructure implementation
 * (a different ORM, an in-memory fake for tests) and nothing above this
 * layer has to change.
 */
interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function create(array $data): User;
}
