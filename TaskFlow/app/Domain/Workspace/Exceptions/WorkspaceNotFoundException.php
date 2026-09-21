<?php

namespace App\Domain\Workspace\Exceptions;

use App\Domain\Shared\DomainException;

class WorkspaceNotFoundException extends DomainException
{
    public function __construct(string $message = 'Workspace not found.')
    {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return 404;
    }
}
