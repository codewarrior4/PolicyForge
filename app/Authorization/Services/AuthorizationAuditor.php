<?php

namespace App\Authorization\Services;

use App\Authorization\DTOs\AuthorizationAuditData;
use App\Events\AuthorizationAllowed;
use App\Events\AuthorizationDenied;
use App\Events\PrivilegeEscalationAttempt;
use App\Events\SensitiveActionAttempted;
use App\Events\TenantAccessDenied;
use Illuminate\Support\Facades\Event;

class AuthorizationAuditor
{
    public function record(AuthorizationAuditData $audit): void
    {
        Event::dispatch($this->eventFor($audit));
    }

    private function eventFor(AuthorizationAuditData $audit): object
    {
        if ($audit->reason === 'tenant_access_denied') {
            return new TenantAccessDenied($audit);
        }

        if ($audit->reason === 'privilege_escalation_attempt') {
            return new PrivilegeEscalationAttempt($audit);
        }

        if (($audit->metadata['sensitive_operation'] ?? false) === true) {
            return new SensitiveActionAttempted($audit);
        }

        if ($audit->allowed) {
            return new AuthorizationAllowed($audit);
        }

        return new AuthorizationDenied($audit);
    }
}
