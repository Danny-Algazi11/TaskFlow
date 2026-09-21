<?php

namespace App\Domain\Label\Exceptions;

use App\Domain\Shared\DomainException;

/**
 * Thrown when attaching a label to a card whose board belongs to a
 * different workspace than the label — labels are a workspace's palette,
 * not a global one, so a card can only wear labels from its own
 * workspace.
 */
class LabelWorkspaceMismatchException extends DomainException
{
    public function __construct(string $message = 'This label does not belong to the card\'s workspace.')
    {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return 422;
    }
}
