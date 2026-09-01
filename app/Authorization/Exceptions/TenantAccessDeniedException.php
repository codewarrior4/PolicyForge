<?php

namespace App\Authorization\Exceptions;

use App\Authorization\DTOs\AuthorizationDecision;
use App\Authorization\Enums\AuthorizationAction;
use App\Authorization\Enums\Permission;

class TenantAccessDeniedException extends AuthorizationDeniedException
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function outsideTenant(
        AuthorizationAction $action,
        Permission|string|null $permission = null,
        array $metadata = [],
    ): self {
        return new self(AuthorizationDecision::deny(
            action: $action,
            reason: 'tenant_access_denied',
            permission: $permission,
            status: 404,
            metadata: $metadata,
        ));
    }
}
