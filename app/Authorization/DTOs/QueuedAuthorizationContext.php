<?php

namespace App\Authorization\DTOs;

use App\Authorization\Enums\AuthorizationAction;
use App\Authorization\Enums\Permission;

readonly class QueuedAuthorizationContext
{
    public function __construct(
        public int $principalId,
        public int $organizationId,
        public int $resourceId,
        public AuthorizationAction $action,
        public Permission $permission,
        public ?string $requestId = null,
    ) {}
}
