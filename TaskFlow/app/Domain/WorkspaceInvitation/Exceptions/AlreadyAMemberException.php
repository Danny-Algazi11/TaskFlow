<?php

namespace App\Domain\WorkspaceInvitation\Exceptions;

use App\Domain\Shared\DomainException;

class AlreadyAMemberException extends DomainException
{
    public function __construct(string $message = 'This person is already a member of the workspace.')
    {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return 422;
    }
}
