<?php

namespace App\Domain\Auth\Exceptions;

use App\Domain\Shared\DomainException;

class InvalidCredentialsException extends DomainException
{
    public function __construct(string $message = 'Invalid credentials.')
    {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return 401;
    }
}
