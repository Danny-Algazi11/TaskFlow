<?php

namespace App\Application\Auth\DTOs;

/**
 * A plain data carrier crossing the boundary from HTTP into business logic.
 * Use Cases never accept a Laravel Request — that would leak an HTTP
 * concern into code that should also work from a console command or a test.
 */
final class RegisterUserData
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
    ) {
    }
}
