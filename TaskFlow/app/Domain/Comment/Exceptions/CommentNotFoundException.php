<?php

namespace App\Domain\Comment\Exceptions;

use App\Domain\Shared\DomainException;

class CommentNotFoundException extends DomainException
{
    public function __construct(string $message = 'Comment not found.')
    {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return 404;
    }
}
