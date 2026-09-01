<?php

namespace App\Authorization\DTOs;

use App\Authorization\Enums\AuthorizationAction;
use App\Authorization\Enums\Permission;
use App\Authorization\Exceptions\AuthorizationDeniedException;
use Illuminate\Auth\Access\Response;

readonly class AuthorizationDecision
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    private function __construct(
        private bool $allowed,
        public AuthorizationAction $action,
        public Permission|string|null $permission = null,
        public ?string $reason = null,
        public int $status = 403,
        public array $metadata = [],
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function allow(
        AuthorizationAction $action,
        Permission|string|null $permission = null,
        array $metadata = [],
    ): self {
        return new self(
            allowed: true,
            action: $action,
            permission: $permission,
            status: 200,
            metadata: $metadata,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function deny(
        AuthorizationAction $action,
        string $reason,
        Permission|string|null $permission = null,
        int $status = 403,
        array $metadata = [],
    ): self {
        return new self(
            allowed: false,
            action: $action,
            permission: $permission,
            reason: $reason,
            status: $status,
            metadata: $metadata,
        );
    }

    public function allowed(): bool
    {
        return $this->allowed;
    }

    public function denied(): bool
    {
        return ! $this->allowed();
    }

    public function authorize(): self
    {
        if ($this->denied()) {
            throw new AuthorizationDeniedException($this);
        }

        return $this;
    }

    public function toLaravelResponse(): Response
    {
        if ($this->allowed()) {
            return Response::allow();
        }

        return Response::denyWithStatus($this->status, code: $this->reason);
    }
}
