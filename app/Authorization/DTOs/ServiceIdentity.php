<?php

namespace App\Authorization\DTOs;

use App\Authorization\Enums\Permission;

readonly class ServiceIdentity
{
    /**
     * @param  array<int, Permission|string>  $permissions
     * @param  array<int, int|string>  $organizationScope
     */
    public function __construct(
        public string $name,
        public array $permissions,
        public array $organizationScope = [],
    ) {}
}
