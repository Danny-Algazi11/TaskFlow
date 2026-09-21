<?php

namespace App\Domain\Label\Exceptions;

use App\Domain\Shared\DomainException;

class LabelNotFoundException extends DomainException
{
    public function __construct(string $message = 'Label not found.')
    {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return 404;
    }
}
