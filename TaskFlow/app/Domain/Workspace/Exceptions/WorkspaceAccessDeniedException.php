<?php

namespace App\Domain\Workspace\Exceptions;

use App\Domain\Shared\DomainException;

/**
 * "You're not a member of this workspace" (view) or "you don't have the
 * role this action needs" (admin-only actions). Reused by the Board
 * feature too — board access is fundamentally workspace membership, so
 * it's the same business rule, not a new one to invent.
 */
class WorkspaceAccessDeniedException extends DomainException
{
    public function __construct(string $message = 'You do not have access to this workspace.')
    {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return 403;
    }
}
