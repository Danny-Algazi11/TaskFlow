<?php

namespace App\Domain\ChecklistItem\Exceptions;

use App\Domain\Shared\DomainException;

class InvalidChecklistItemReorderException extends DomainException
{
    public function __construct(string $message = 'The given item ids do not match this card\'s checklist items.')
    {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return 422;
    }
}
