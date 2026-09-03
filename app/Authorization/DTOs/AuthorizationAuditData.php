<?php

namespace App\Authorization\DTOs;

use App\Authorization\Enums\AuthorizationAction;
use App\Authorization\Enums\Permission;
use DateTimeImmutable;
use DateTimeInterface;

readonly class AuthorizationAuditData
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public int|string|null $principalId,
        public AuthorizationAction $action,
        public Permission|string|null $permission,
        public int|string|null $organizationId,
        public int|string|null $resourceId,
        public bool $allowed,
        public ?string $reason,
        public ?string $requestId,
        public DateTimeInterface $occurredAt,
        public array $metadata = [],
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function capture(
        int|string|null $principalId,
        AuthorizationAction $action,
        Permission|string|null $permission,
        int|string|null $organizationId,
        int|string|null $resourceId,
        bool $allowed,
        ?string $reason = null,
        ?string $requestId = null,
        array $metadata = [],
    ): self {
        return new self(
            principalId: $principalId,
            action: $action,
            permission: $permission,
            organizationId: $organizationId,
            resourceId: $resourceId,
            allowed: $allowed,
            reason: $reason,
            requestId: $requestId,
            occurredAt: new DateTimeImmutable,
            metadata: self::scrub($metadata),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'principal_id' => $this->principalId,
            'action' => $this->action->value,
            'permission' => $this->permission instanceof Permission ? $this->permission->value : $this->permission,
            'organization_id' => $this->organizationId,
            'resource_id' => $this->resourceId,
            'allowed' => $this->allowed,
            'reason' => $this->reason,
            'request_id' => $this->requestId,
            'occurred_at' => $this->occurredAt->format(DateTimeInterface::ATOM),
            'metadata' => $this->metadata,
        ];
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private static function scrub(array $metadata): array
    {
        $safe = [];

        foreach ($metadata as $key => $value) {
            if (str($key)->lower()->contains(['password', 'token', 'secret', 'credential', 'api_key'])) {
                continue;
            }

            $safe[$key] = is_array($value) ? self::scrub($value) : $value;
        }

        return $safe;
    }
}
