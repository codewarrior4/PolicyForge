<?php

namespace App\Authorization\DTOs;

use App\Models\Organization;
use App\Models\User;

readonly class McpAuthorizationContext
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public ?User $user,
        public ?Organization $organization,
        public string $tool,
        public ?string $feature = null,
        public array $metadata = [],
    ) {}
}
