<?php

namespace App\Authorization\Services;

use App\Authorization\DTOs\AuthorizationContext;
use App\Authorization\Enums\AuthorizationAction;
use App\Authorization\Enums\Permission;
use Illuminate\Http\Request;

class AuthorizationContextFactory
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function fromRequest(
        Request $request,
        AuthorizationAction $action,
        Permission|string|null $permission = null,
        object|string|int|null $organization = null,
        object|string|null $resource = null,
        ?string $feature = null,
        array $metadata = [],
    ): AuthorizationContext {
        return new AuthorizationContext(
            principal: $request->user(),
            action: $action,
            permission: $permission,
            organization: $organization,
            resource: $resource,
            feature: $feature,
            request: $request,
            metadata: $metadata,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function make(
        AuthorizationAction $action,
        Permission|string|null $permission = null,
        object|string|int|null $organization = null,
        object|string|null $resource = null,
        ?string $feature = null,
        array $metadata = [],
    ): AuthorizationContext {
        return new AuthorizationContext(
            principal: null,
            action: $action,
            permission: $permission,
            organization: $organization,
            resource: $resource,
            feature: $feature,
            metadata: $metadata,
        );
    }
}
