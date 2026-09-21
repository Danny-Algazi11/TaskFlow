<?php

namespace App\Domain\WorkspaceInvitation\Exceptions;

use App\Domain\Shared\DomainException;

class PendingInvitationAlreadyExistsException extends DomainException
{
    public function __construct(string $message = 'There is already a pending invitation for this email.')
    {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return 422;
    }
}
