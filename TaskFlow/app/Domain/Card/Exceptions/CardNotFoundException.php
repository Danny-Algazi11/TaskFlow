<?php

namespace App\Domain\Card\Exceptions;

use App\Domain\Shared\DomainException;

class CardNotFoundException extends DomainException
{
    public function __construct(string $message = 'Card not found.')
    {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return 404;
    }
}
