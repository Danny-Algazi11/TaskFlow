<?php

namespace App\Domain\Card\Exceptions;

use App\Domain\Shared\DomainException;

/**
 * Thrown when a reorder call includes a card id that doesn't exist, or
 * that currently belongs to a different board than the target list —
 * drag-and-drop between lists on the SAME board is the feature; reaching
 * into another board (and thus possibly another workspace) is not.
 */
class InvalidCardReorderException extends DomainException
{
    public function __construct(string $message = 'One or more cards cannot be moved into this list.')
    {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return 422;
    }
}
