<?php

namespace App\Authorization\Services;

use App\Authorization\Enums\Permission;
use InvalidArgumentException;
use LogicException;

class PermissionRegistry
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $permissions = [];

    public function register(Permission|string $permission, array $metadata = []): self
    {
        $permissionName = $this->normalize($permission);

        if (! $this->isValidName($permissionName)) {
            throw new InvalidArgumentException("Invalid permission name [{$permissionName}].");
        }

        if ($this->has($permissionName)) {
            throw new LogicException("Permission [{$permissionName}] is already registered.");
        }

        $this->permissions[$permissionName] = $metadata;

        return $this;
    }

    public function has(Permission|string $permission): bool
    {
        return array_key_exists($this->normalize($permission), $this->permissions);
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(Permission|string $permission): array
    {
        $permissionName = $this->normalize($permission);

        if (! $this->has($permissionName)) {
            throw new InvalidArgumentException("Permission [{$permissionName}] is not registered.");
        }

        return $this->permissions[$permissionName];
    }

    /**
     * @return array<int, string>
     */
    public function names(): array
    {
        return array_keys($this->permissions);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->permissions;
    }

    public function isValidName(string $permission): bool
    {
        return preg_match('/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+$/', $permission) === 1;
    }

    private function normalize(Permission|string $permission): string
    {
        if ($permission instanceof Permission) {
            return $permission->value;
        }

        return $permission;
    }
}
