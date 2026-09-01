<?php

namespace App\Authorization\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use InvalidArgumentException;

class UnknownPermissionException extends InvalidArgumentException implements ShouldntReport
{
    public static function forName(string $permission): self
    {
        return new self("Unknown permission [{$permission}].");
    }
}
