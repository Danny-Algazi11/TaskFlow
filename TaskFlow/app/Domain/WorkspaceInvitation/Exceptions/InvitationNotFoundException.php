<?php

namespace App\Domain\WorkspaceInvitation\Exceptions;

use App\Domain\Shared\DomainException;

class InvitationNotFoundException extends DomainException
{
    public function __construct(string $message = 'This invitation is invalid or has already been used.')
    {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return 404;
    }
}
