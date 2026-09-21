<?php

namespace App\Domain\ChecklistItem\Exceptions;

use App\Domain\Shared\DomainException;

class ChecklistItemNotFoundException extends DomainException
{
    public function __construct(string $message = 'Checklist item not found.')
    {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return 404;
    }
}
