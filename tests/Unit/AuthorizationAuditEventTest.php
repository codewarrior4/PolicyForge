<?php

namespace Tests\Unit;

use App\Authorization\DTOs\AuthorizationAuditData;
use App\Authorization\Enums\AuthorizationAction;
use App\Authorization\Enums\Permission;
use App\Events\AuthorizationAllowed;
use App\Events\AuthorizationDenied;
use App\Events\PrivilegeEscalationAttempt;
use App\Events\SensitiveActionAttempted;
use App\Events\TenantAccessDenied;
use PHPUnit\Framework\TestCase;

class AuthorizationAuditEventTest extends TestCase
{
    public function test_audit_data_captures_decision_without_secrets(): void
    {
        $audit = AuthorizationAuditData::capture(
            principalId: 1,
            action: AuthorizationAction::Execute,
            permission: Permission::McpExecute,
            organizationId: 2,
            resourceId: 'policy.search',
            allowed: false,
            reason: 'feature_disabled',
            requestId: 'req-123',
            metadata: [
                'ip' => '127.0.0.1',
                'api_token' => 'secret-token',
                'nested' => ['password' => 'secret', 'safe' => 'value'],
            ],
        );

        $payload = $audit->toArray();

        $this->assertSame('execute', $payload['action']);
        $this->assertSame('mcp.execute', $payload['permission']);
        $this->assertSame('feature_disabled', $payload['reason']);
        $this->assertSame(['ip' => '127.0.0.1', 'nested' => ['safe' => 'value']], $payload['metadata']);
    }

    public function test_authorization_events_carry_audit_payload(): void
    {
        $audit = AuthorizationAuditData::capture(
            principalId: 1,
            action: AuthorizationAction::View,
            permission: Permission::OrganizationsView,
            organizationId: 2,
            resourceId: 3,
            allowed: true,
            requestId: 'req-123',
        );

        $this->assertSame($audit, (new AuthorizationAllowed($audit))->audit);
        $this->assertSame($audit, (new AuthorizationDenied($audit))->audit);
        $this->assertSame($audit, (new PrivilegeEscalationAttempt($audit))->audit);
        $this->assertSame($audit, (new TenantAccessDenied($audit))->audit);
        $this->assertSame($audit, (new SensitiveActionAttempted($audit))->audit);
    }
}
