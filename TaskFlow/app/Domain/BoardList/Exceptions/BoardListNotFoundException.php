<?php

namespace App\Domain\BoardList\Exceptions;

use App\Domain\Shared\DomainException;

class BoardListNotFoundException extends DomainException
{
    public function __construct(string $message = 'List not found.')
    {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return 404;
    }
}
