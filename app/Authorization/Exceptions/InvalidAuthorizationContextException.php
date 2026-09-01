<?php

namespace App\Authorization\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use InvalidArgumentException;

class InvalidAuthorizationContextException extends InvalidArgumentException implements ShouldntReport
{
    public static function missingPermission(): self
    {
        return new self('Authorization context is missing a permission.');
    }
}
