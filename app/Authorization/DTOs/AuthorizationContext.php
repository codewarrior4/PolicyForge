<?php

namespace App\Authorization\DTOs;

use App\Authorization\Enums\AuthorizationAction;
use App\Authorization\Enums\Permission;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

readonly class AuthorizationContext
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public ?Authenticatable $principal,
        public AuthorizationAction $action,
        public Permission|string|null $permission = null,
        public object|string|int|null $organization = null,
        public object|string|null $resource = null,
        public ?string $feature = null,
        public ?Request $request = null,
        public array $metadata = [],
    ) {}
}
