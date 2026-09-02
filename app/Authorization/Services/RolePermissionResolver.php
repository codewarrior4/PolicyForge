<?php

namespace App\Authorization\Services;

use App\Authorization\Enums\Permission;
use App\Authorization\Enums\Role;
use App\Authorization\Exceptions\UnknownPermissionException;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class RolePermissionResolver
{
    /**
     * @return array<int, Permission>
     */
    public function baselinePermissions(Role|string $role): array
    {
        return match ($this->normalizeRole($role)) {
            Role::Owner => Permission::cases(),
            Role::Administrator => [
                Permission::OrganizationsView,
                Permission::OrganizationsUpdate,
                Permission::OrganizationsManageMembers,
                Permission::UsersView,
                Permission::UsersCreate,
                Permission::UsersUpdate,
                Permission::UsersInvite,
                Permission::UsersDisable,
                Permission::RolesView,
                Permission::RolesAssign,
                Permission::RolesRevoke,
                Permission::PermissionsView,
                Permission::PasskeysView,
                Permission::PasskeysRegister,
                Permission::PasskeysRevoke,
                Permission::AuditView,
                Permission::AuditExport,
                Permission::McpExecute,
                Permission::McpToolsView,
                Permission::ApiKeysView,
                Permission::ApiKeysCreate,
            ],
            Role::Developer => [
                Permission::OrganizationsView,
                Permission::UsersView,
                Permission::RolesView,
                Permission::PasskeysView,
                Permission::PasskeysRegister,
                Permission::PasskeysRevoke,
                Permission::AuditView,
                Permission::McpExecute,
                Permission::McpToolsView,
                Permission::ApiKeysView,
                Permission::ApiKeysCreate,
                Permission::ApiKeysRotate,
            ],
            Role::Member => [
                Permission::OrganizationsView,
                Permission::UsersView,
                Permission::PasskeysView,
                Permission::PasskeysRegister,
                Permission::McpExecute,
            ],
            Role::Viewer => [
                Permission::OrganizationsView,
                Permission::UsersView,
                Permission::RolesView,
                Permission::AuditView,
            ],
        };
    }

    /**
     * @return array<int, Permission>
     */
    public function effectivePermissions(Role|string $role, ?Organization $organization = null): array
    {
        $normalizedRole = $this->normalizeRole($role);
        $permissions = collect($this->baselinePermissions($normalizedRole))
            ->keyBy(fn (Permission $permission): string => $permission->value);

        if ($organization === null) {
            return $permissions->values()->all();
        }

        $this->overridesFor($organization, $normalizedRole)
            ->each(function (object $override) use ($permissions): void {
                $permission = $this->normalizePermission($override->permission);

                if ($override->effect === 'revoke') {
                    $permissions->forget($permission->value);

                    return;
                }

                if ($override->effect !== 'grant') {
                    throw new InvalidArgumentException("Invalid organization permission override effect [{$override->effect}].");
                }

                if (! $this->canGrantByOrganizationOverride($permission)) {
                    throw new InvalidArgumentException("Permission [{$permission->value}] cannot be granted by organization override.");
                }

                $permissions->put($permission->value, $permission);
            });

        return $permissions->values()->all();
    }

    public function allows(Role|string $role, Permission|string $permission, ?Organization $organization = null): bool
    {
        $permission = $this->normalizePermission($permission);

        return collect($this->effectivePermissions($role, $organization))
            ->contains(fn (Permission $resolvedPermission): bool => $resolvedPermission === $permission);
    }

    /**
     * @return array<int, Permission>
     */
    public function permissionsForMembership(User $user, Organization $organization): array
    {
        $membership = $user->organizations()
            ->whereKey($organization->getKey())
            ->first();

        if ($membership === null) {
            return [];
        }

        return $this->effectivePermissions((string) $membership->pivot->role, $organization);
    }

    private function normalizeRole(Role|string $role): Role
    {
        if ($role instanceof Role) {
            return $role;
        }

        return Role::tryFrom($role)
            ?? throw new InvalidArgumentException("Unknown role [{$role}].");
    }

    private function normalizePermission(Permission|string $permission): Permission
    {
        if ($permission instanceof Permission) {
            return $permission;
        }

        return Permission::tryFrom($permission)
            ?? throw UnknownPermissionException::forName($permission);
    }

    private function canGrantByOrganizationOverride(Permission $permission): bool
    {
        return ! in_array($permission, [
            Permission::OrganizationsDelete,
            Permission::OrganizationsTransferOwnership,
            Permission::PermissionsGrant,
            Permission::PermissionsRevoke,
            Permission::SensitiveOperationsApprove,
            Permission::SensitiveOperationsExecute,
            Permission::McpAdmin,
            Permission::ApiKeysRevoke,
            Permission::ApiKeysRotate,
        ], true);
    }

    /**
     * @return Collection<int, object{permission: string, effect: string}>
     */
    private function overridesFor(Organization $organization, Role $role): Collection
    {
        return $organization->permissionOverrides()
            ->where('role', $role->value)
            ->get(['permission', 'effect']);
    }
}
