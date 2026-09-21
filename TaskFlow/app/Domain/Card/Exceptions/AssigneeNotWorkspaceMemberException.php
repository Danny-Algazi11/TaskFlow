<?php

namespace App\Domain\Card\Exceptions;

use App\Domain\Shared\DomainException;

/**
 * Thrown when assigning a card to a user who isn't a member of the
 * workspace that card lives in — you can only hand work to people who
 * are actually part of the workspace.
 */
class AssigneeNotWorkspaceMemberException extends DomainException
{
    public function __construct(string $message = 'This user is not a member of the card\'s workspace.')
    {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return 422;
    }
}
