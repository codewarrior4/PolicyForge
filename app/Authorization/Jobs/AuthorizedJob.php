<?php

namespace App\Authorization\Jobs;

use App\Authorization\DTOs\QueuedAuthorizationContext;
use App\Authorization\Enums\AuthorizationAction;
use App\Authorization\Enums\Permission;
use App\Authorization\Services\RolePermissionResolver;
use App\Authorization\Services\TenantScopedResourceResolver;
use App\Models\Organization;
use App\Models\PolicyDocument;
use App\Models\User;

abstract class AuthorizedJob
{
    protected function authorizePolicyDocument(
        QueuedAuthorizationContext $context,
        RolePermissionResolver $rolePermissionResolver,
        TenantScopedResourceResolver $tenantScopedResourceResolver,
    ): PolicyDocument {
        $user = User::query()->findOrFail($context->principalId);
        $organization = Organization::query()->findOrFail($context->organizationId);
        $policyDocument = $tenantScopedResourceResolver->policyDocumentById($organization, $context->resourceId);

        if (! $rolePermissionResolver->allows(
            role: $this->roleFor($user, $organization),
            permission: $context->permission,
            organization: $organization,
        )) {
            abort(403, 'Queued action is no longer authorized.');
        }

        return $policyDocument;
    }

    protected function queuedAuthorizationContext(
        int $principalId,
        int $organizationId,
        int $resourceId,
        AuthorizationAction $action,
        Permission $permission,
        ?string $requestId = null,
    ): QueuedAuthorizationContext {
        return new QueuedAuthorizationContext(
            principalId: $principalId,
            organizationId: $organizationId,
            resourceId: $resourceId,
            action: $action,
            permission: $permission,
            requestId: $requestId,
        );
    }

    private function roleFor(User $user, Organization $organization): string
    {
        $membership = $user->organizations()
            ->whereKey($organization->getKey())
            ->first();

        if ($membership === null) {
            abort(404, 'Organization membership was not found.');
        }

        return (string) $membership->pivot->role;
    }
}
