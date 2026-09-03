<?php

namespace Tests\Unit;

use App\Authorization\DTOs\AuthorizationAuditData;
use App\Authorization\Enums\AuthorizationAction;
use App\Authorization\Enums\Permission;
use App\Authorization\Services\AuthorizationAuditor;
use App\Events\AuthorizationAllowed;
use App\Events\AuthorizationDenied;
use App\Events\PrivilegeEscalationAttempt;
use App\Events\SensitiveActionAttempted;
use App\Events\TenantAccessDenied;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AuthorizationAuditorTest extends TestCase
{
    public function test_records_allowed_and_denied_events(): void
    {
        Event::fake([AuthorizationAllowed::class, AuthorizationDenied::class]);

        (new AuthorizationAuditor)->record($this->audit(allowed: true));
        (new AuthorizationAuditor)->record($this->audit(allowed: false, reason: 'missing_permission'));

        Event::assertDispatched(AuthorizationAllowed::class);
        Event::assertDispatched(AuthorizationDenied::class);
    }

    public function test_records_specific_security_events(): void
    {
        Event::fake([
            PrivilegeEscalationAttempt::class,
            SensitiveActionAttempted::class,
            TenantAccessDenied::class,
        ]);

        (new AuthorizationAuditor)->record($this->audit(allowed: false, reason: 'tenant_access_denied'));
        (new AuthorizationAuditor)->record($this->audit(allowed: false, reason: 'privilege_escalation_attempt'));
        (new AuthorizationAuditor)->record($this->audit(
            allowed: false,
            reason: 'step_up_required',
            metadata: ['sensitive_operation' => true],
        ));

        Event::assertDispatched(TenantAccessDenied::class);
        Event::assertDispatched(PrivilegeEscalationAttempt::class);
        Event::assertDispatched(SensitiveActionAttempted::class);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function audit(bool $allowed, ?string $reason = null, array $metadata = []): AuthorizationAuditData
    {
        return AuthorizationAuditData::capture(
            principalId: 1,
            action: AuthorizationAction::Execute,
            permission: Permission::McpExecute,
            organizationId: 2,
            resourceId: 'policy.search',
            allowed: $allowed,
            reason: $reason,
            requestId: 'req-123',
            metadata: $metadata,
        );
    }
}
