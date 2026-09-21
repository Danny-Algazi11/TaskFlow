<?php

namespace App\Domain\WorkspaceInvitation\Exceptions;

use App\Domain\Shared\DomainException;

/**
 * The invitation is scoped to the email address it was sent to — accepting
 * it requires being logged in as that address, not just possessing the
 * token. Stops a leaked invite link from being claimed by whoever finds it.
 */
class InvitationEmailMismatchException extends DomainException
{
    public function __construct(string $message = 'This invitation was sent to a different email address.')
    {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return 403;
    }
}
