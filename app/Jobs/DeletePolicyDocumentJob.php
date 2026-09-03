<?php

namespace App\Jobs;

use App\Authorization\DTOs\QueuedAuthorizationContext;
use App\Authorization\Enums\AuthorizationAction;
use App\Authorization\Enums\Permission;
use App\Authorization\Jobs\AuthorizedJob;
use App\Authorization\Services\RolePermissionResolver;
use App\Authorization\Services\TenantScopedResourceResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeletePolicyDocumentJob extends AuthorizedJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public readonly QueuedAuthorizationContext $authorizationContext,
    ) {}

    public static function forPolicyDocument(
        int $principalId,
        int $organizationId,
        int $policyDocumentId,
        ?string $requestId = null,
    ): self {
        return new self(new QueuedAuthorizationContext(
            principalId: $principalId,
            organizationId: $organizationId,
            resourceId: $policyDocumentId,
            action: AuthorizationAction::Delete,
            permission: Permission::OrganizationsDelete,
            requestId: $requestId,
        ));
    }

    public function handle(
        RolePermissionResolver $rolePermissionResolver,
        TenantScopedResourceResolver $tenantScopedResourceResolver,
    ): void {
        $policyDocument = $this->authorizePolicyDocument(
            context: $this->authorizationContext,
            rolePermissionResolver: $rolePermissionResolver,
            tenantScopedResourceResolver: $tenantScopedResourceResolver,
        );

        $policyDocument->delete();
    }
}
