<?php

namespace App\Authorization\DTOs;

use App\Models\Organization;
use Illuminate\Contracts\Auth\Authenticatable;

readonly class OrganizationContext
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public ?Organization $organization,
        public ?Authenticatable $principal,
        public string $source,
        public ?int $requestedOrganizationId = null,
        public array $metadata = [],
    ) {}

    public function resolved(): bool
    {
        return $this->organization !== null;
    }
}
