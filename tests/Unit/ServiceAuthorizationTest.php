<?php

namespace Tests\Unit;

use App\Authorization\DTOs\ServiceIdentity;
use App\Authorization\Enums\Permission;
use App\Authorization\Services\ServiceAuthorizationService;
use PHPUnit\Framework\TestCase;

class ServiceAuthorizationTest extends TestCase
{
    public function test_service_with_permission_and_scope_is_allowed(): void
    {
        $service = new ServiceIdentity(
            name: 'mcp-service',
            permissions: [Permission::McpExecute],
            organizationScope: [10],
        );

        $decision = (new ServiceAuthorizationService)->inspect($service, Permission::McpExecute, 10);

        $this->assertTrue($decision->allowed());
    }

    public function test_internal_service_without_permission_is_denied(): void
    {
        $service = new ServiceIdentity(
            name: 'analytics-service',
            permissions: [Permission::AuditView],
            organizationScope: ['*'],
        );

        $decision = (new ServiceAuthorizationService)->inspect($service, Permission::McpExecute, 10);

        $this->assertTrue($decision->denied());
        $this->assertSame('service_permission_denied', $decision->reason);
    }

    public function test_internal_service_outside_resource_scope_is_hidden(): void
    {
        $service = new ServiceIdentity(
            name: 'billing-service',
            permissions: [Permission::AuditExport],
            organizationScope: [10],
        );

        $decision = (new ServiceAuthorizationService)->inspect($service, Permission::AuditExport, 20);

        $this->assertTrue($decision->denied());
        $this->assertSame('service_scope_denied', $decision->reason);
        $this->assertSame(404, $decision->status);
    }
}
