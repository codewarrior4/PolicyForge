<?php

namespace App\Authorization\Policies;

use App\Authorization\DTOs\AuthorizationContext;
use App\Authorization\DTOs\AuthorizationDecision;
use App\Authorization\Enums\Permission;
use App\Authorization\Services\AuthorizationService;
use App\Models\User;
use Illuminate\Auth\Access\Response;

abstract class ContextPolicy
{
    public function __construct(
        private readonly AuthorizationService $authorizationService,
    ) {}

    protected function inspect(
        User $user,
        AuthorizationContext $context,
        Permission $permission,
        bool $requiresOrganizationMatch = true,
        bool $requiresResourceOwnership = false,
        bool $requiresActiveResource = true,
    ): Response {
        $context = $context->forPrincipal($user)->withPermission($permission);

        if ($requiresOrganizationMatch && ($context->metadata['organization_matches'] ?? true) === false) {
            return AuthorizationDecision::deny(
                action: $context->action,
                reason: 'tenant_access_denied',
                permission: $permission,
                status: 404,
            )->toLaravelResponse();
        }

        if ($requiresActiveResource && ($context->metadata['resource_active'] ?? true) === false) {
            return AuthorizationDecision::deny(
                action: $context->action,
                reason: 'resource_inactive',
                permission: $permission,
            )->toLaravelResponse();
        }

        if ($requiresResourceOwnership
            && ($context->metadata['owns_resource'] ?? false) === false
            && ($context->metadata['can_manage_resource'] ?? false) === false) {
            return AuthorizationDecision::deny(
                action: $context->action,
                reason: 'resource_ownership_failed',
                permission: $permission,
            )->toLaravelResponse();
        }

        return $this->authorizationService->inspect($context)->toLaravelResponse();
    }
}
