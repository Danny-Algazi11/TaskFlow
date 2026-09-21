<?php

namespace App\Domain\Comment\Exceptions;

use App\Domain\Shared\DomainException;

/**
 * Deleting a comment is author-only — narrower than "any workspace
 * member", which is the rule for the card itself. A different rule from
 * WorkspaceAccessDeniedException, so it gets its own exception rather
 * than reusing that one just because both happen to map to 403.
 */
class CommentAuthorMismatchException extends DomainException
{
    public function __construct(string $message = 'Only the comment\'s author can delete it.')
    {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return 403;
    }
}
