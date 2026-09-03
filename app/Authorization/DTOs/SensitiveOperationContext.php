<?php

namespace App\Authorization\DTOs;

use App\Authorization\Enums\SensitiveOperation;
use App\Models\Organization;
use App\Models\User;

readonly class SensitiveOperationContext
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public User $user,
        public Organization $organization,
        public SensitiveOperation $operation,
        public bool $stepUpSatisfied,
        public array $metadata = [],
    ) {}
}
