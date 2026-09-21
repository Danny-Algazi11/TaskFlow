<?php

namespace App\Domain\BoardList\Exceptions;

use App\Domain\Shared\DomainException;

/**
 * Thrown when the ids sent to reorder a board's lists aren't exactly the
 * set of lists that board currently has — missing an id would silently
 * strand a list with a stale position, and a foreign id would let one
 * board's reorder call reach into another board's list.
 */
class InvalidListReorderException extends DomainException
{
    public function __construct(string $message = 'The given list ids do not match this board\'s lists.')
    {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return 422;
    }
}
