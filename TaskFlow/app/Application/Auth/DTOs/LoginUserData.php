<?php

namespace App\Application\Auth\DTOs;

final class LoginUserData
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
    ) {
    }
}
