<?php

namespace App\Authorization\Services;

use App\Authorization\DTOs\AuthorizationContext;
use App\Authorization\DTOs\AuthorizationDecision;
use App\Authorization\Enums\Permission;
use App\Authorization\Exceptions\InvalidAuthorizationContextException;
use App\Authorization\Exceptions\UnknownPermissionException;

class AuthorizationService
{
    public function __construct(
        private readonly PermissionRegistry $permissionRegistry,
    ) {}

    public function authorize(AuthorizationContext $context): AuthorizationDecision
    {
        return $this->inspect($context)->authorize();
    }

    public function allows(AuthorizationContext $context): bool
    {
        return $this->inspect($context)->allowed();
    }

    public function denies(AuthorizationContext $context): bool
    {
        return $this->inspect($context)->denied();
    }

    public function inspect(AuthorizationContext $context): AuthorizationDecision
    {
        if ($context->principal === null) {
            return AuthorizationDecision::deny(
                action: $context->action,
                reason: 'unauthenticated',
                permission: $context->permission,
                status: 401,
            );
        }

        if ($context->permission === null) {
            return AuthorizationDecision::deny(
                action: $context->action,
                reason: 'missing_permission',
            );
        }

        $permissionName = $this->permissionName($context->permission);

        if (! $this->permissionRegistry->isValidName($permissionName)) {
            throw UnknownPermissionException::forName($permissionName);
        }

        if (! $this->permissionRegistry->has($permissionName)) {
            throw UnknownPermissionException::forName($permissionName);
        }

        if ($context->feature !== null && ($context->metadata['feature_enabled'] ?? true) === false) {
            return AuthorizationDecision::deny(
                action: $context->action,
                reason: 'feature_disabled',
                permission: $context->permission,
            );
        }

        return AuthorizationDecision::allow(
            action: $context->action,
            permission: $context->permission,
            metadata: [
                'permission' => $permissionName,
            ],
        );
    }

    private function permissionName(Permission|string $permission): string
    {
        if ($permission instanceof Permission) {
            return $permission->value;
        }

        if ($permission === '') {
            throw InvalidAuthorizationContextException::missingPermission();
        }

        return $permission;
    }
}
