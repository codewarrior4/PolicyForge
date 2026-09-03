<?php

namespace App\Authorization\Services;

use App\Authorization\DTOs\AuthorizationContext;
use App\Authorization\DTOs\AuthorizationDecision;
use App\Authorization\DTOs\McpAuthorizationContext;
use App\Authorization\Enums\AuthorizationAction;
use App\Authorization\Enums\Permission;

class CanExecuteMcpTool
{
    public function __construct(
        private readonly AuthorizationService $authorizationService,
        private readonly RolePermissionResolver $rolePermissionResolver,
    ) {}

    public function inspect(McpAuthorizationContext $context): AuthorizationDecision
    {
        if ($context->user === null) {
            return AuthorizationDecision::deny(
                action: AuthorizationAction::Execute,
                reason: 'unauthenticated',
                permission: Permission::McpExecute,
                status: 401,
            );
        }

        if ($context->organization === null) {
            return AuthorizationDecision::deny(
                action: AuthorizationAction::Execute,
                reason: 'missing_organization_context',
                permission: Permission::McpExecute,
            );
        }

        $membership = $context->user->organizations()
            ->whereKey($context->organization->getKey())
            ->first();

        if ($membership === null || ! $this->rolePermissionResolver->allows(
            role: (string) $membership->pivot->role,
            permission: Permission::McpExecute,
            organization: $context->organization,
        )) {
            return AuthorizationDecision::deny(
                action: AuthorizationAction::Execute,
                reason: 'missing_permission',
                permission: Permission::McpExecute,
            );
        }

        return $this->authorizationService->inspect(new AuthorizationContext(
            principal: $context->user,
            action: AuthorizationAction::Execute,
            permission: Permission::McpExecute,
            organization: $context->organization,
            resource: $context->tool,
            feature: $context->feature,
            metadata: $context->metadata,
        ));
    }
}
