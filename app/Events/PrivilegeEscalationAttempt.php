<?php

namespace App\Events;

use App\Authorization\DTOs\AuthorizationAuditData;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

readonly class PrivilegeEscalationAttempt
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public AuthorizationAuditData $audit,
    ) {}
}
