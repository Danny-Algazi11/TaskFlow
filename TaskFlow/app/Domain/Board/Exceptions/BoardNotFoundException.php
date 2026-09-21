<?php

namespace App\Domain\Board\Exceptions;

use App\Domain\Shared\DomainException;

class BoardNotFoundException extends DomainException
{
    public function __construct(string $message = 'Board not found.')
    {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return 404;
    }
}
