<?php

namespace Database\Factories;

use App\Authorization\Enums\Permission;
use App\Authorization\Enums\Role;
use App\Models\Organization;
use App\Models\OrganizationPermissionOverride;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationPermissionOverride>
 */
class OrganizationPermissionOverrideFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'role' => Role::Member->value,
            'permission' => Permission::AuditView->value,
            'effect' => 'grant',
        ];
    }

    public function grant(Permission $permission): static
    {
        return $this->state(fn (): array => [
            'permission' => $permission->value,
            'effect' => 'grant',
        ]);
    }

    public function revoke(Permission $permission): static
    {
        return $this->state(fn (): array => [
            'permission' => $permission->value,
            'effect' => 'revoke',
        ]);
    }

    public function forRole(Role $role): static
    {
        return $this->state(fn (): array => [
            'role' => $role->value,
        ]);
    }
}
