<?php

namespace Tests\Unit;

use App\Authorization\DTOs\AuthorizationDecision;
use App\Authorization\Enums\AuthorizationAction;
use App\Authorization\Enums\Permission;
use App\Authorization\Exceptions\AuthorizationDeniedException;
use App\Authorization\Exceptions\TenantAccessDeniedException;
use PHPUnit\Framework\TestCase;

class AuthorizationExceptionTest extends TestCase
{
    public function test_authorization_denied_exception_carries_safe_status_and_context(): void
    {
        $exception = new AuthorizationDeniedException(AuthorizationDecision::deny(
            action: AuthorizationAction::Delete,
            reason: 'missing_permission',
            permission: Permission::OrganizationsDelete,
            status: 403,
        ));

        $this->assertSame('This action is unauthorized.', $exception->getMessage());
        $this->assertSame(403, $exception->status());
        $this->assertSame(
            [
                'reason' => 'missing_permission',
                'action' => 'delete',
                'permission' => 'organizations.delete',
                'status' => 403,
            ],
            $exception->context(),
        );
    }

    public function test_tenant_access_denied_exception_hides_resource_existence(): void
    {
        $exception = TenantAccessDeniedException::outsideTenant(
            action: AuthorizationAction::View,
            permission: Permission::OrganizationsView,
            metadata: ['organization_id' => 99],
        );

        $this->assertSame(404, $exception->status());
        $this->assertSame('tenant_access_denied', $exception->decision->reason);
        $this->assertSame(['organization_id' => 99], $exception->decision->metadata);
    }
}
