<?php

namespace App\Authorization\Services;

use App\Authorization\DTOs\AuthorizationDecision;
use App\Authorization\DTOs\SensitiveOperationContext;
use App\Authorization\Enums\AuthorizationAction;

class SensitiveOperationAuthorizer
{
    public function __construct(
        private readonly RolePermissionResolver $rolePermissionResolver,
    ) {}

    public function inspect(SensitiveOperationContext $context): AuthorizationDecision
    {
        $permission = $context->operation->permission();
        $membership = $context->user->organizations()
            ->whereKey($context->organization->getKey())
            ->first();

        if ($membership === null || ! $this->rolePermissionResolver->allows(
            role: (string) $membership->pivot->role,
            permission: $permission,
            organization: $context->organization,
        )) {
            return AuthorizationDecision::deny(
                action: AuthorizationAction::Approve,
                reason: 'missing_permission',
                permission: $permission,
            );
        }

        if (! $context->stepUpSatisfied) {
            return AuthorizationDecision::deny(
                action: AuthorizationAction::Approve,
                reason: 'step_up_required',
                permission: $permission,
            );
        }

        return AuthorizationDecision::allow(
            action: AuthorizationAction::Approve,
            permission: $permission,
            metadata: [
                'operation' => $context->operation->value,
                'organization_id' => $context->organization->getKey(),
            ],
        );
    }
}
