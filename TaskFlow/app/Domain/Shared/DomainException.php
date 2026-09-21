<?php

namespace App\Domain\Shared;

use Exception;

/**
 * Base for every business-rule failure across all features. The one thing
 * every Domain exception must answer is "what HTTP status does this map
 * to" — which lets a single handler in bootstrap/app.php translate ANY
 * Domain exception into a JSON response, instead of each controller action
 * repeating its own try/catch. New feature, new exception, zero new
 * plumbing: it just extends this and the mapping already exists.
 */
abstract class DomainException extends Exception
{
    abstract public function httpStatus(): int;
}
