<?php

namespace Tests\Unit;

use App\Authorization\Enums\Permission;
use App\Authorization\Enums\Role;
use App\Authorization\Exceptions\UnknownPermissionException;
use App\Authorization\Services\RolePermissionResolver;
use App\Models\Organization;
use App\Models\OrganizationPermissionOverride;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class RolePermissionResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_receives_every_registered_permission(): void
    {
        $permissions = app(RolePermissionResolver::class)->baselinePermissions(Role::Owner);

        $this->assertSame(Permission::cases(), $permissions);
    }

    public function test_viewer_role_is_read_only(): void
    {
        $resolver = app(RolePermissionResolver::class);

        $this->assertTrue($resolver->allows(Role::Viewer, Permission::OrganizationsView));
        $this->assertTrue($resolver->allows(Role::Viewer, Permission::AuditView));
        $this->assertFalse($resolver->allows(Role::Viewer, Permission::UsersUpdate));
        $this->assertFalse($resolver->allows(Role::Viewer, Permission::McpExecute));
    }

    public function test_organization_override_can_grant_safe_permission_to_role(): void
    {
        $organization = Organization::factory()->create();
        OrganizationPermissionOverride::factory()
            ->for($organization)
            ->forRole(Role::Member)
            ->grant(Permission::AuditView)
            ->create();

        $this->assertTrue(app(RolePermissionResolver::class)->allows(
            role: Role::Member,
            permission: Permission::AuditView,
            organization: $organization,
        ));
    }

    public function test_organization_override_can_revoke_baseline_permission_from_role(): void
    {
        $organization = Organization::factory()->create();
        OrganizationPermissionOverride::factory()
            ->for($organization)
            ->forRole(Role::Developer)
            ->revoke(Permission::McpExecute)
            ->create();

        $this->assertFalse(app(RolePermissionResolver::class)->allows(
            role: Role::Developer,
            permission: Permission::McpExecute,
            organization: $organization,
        ));
    }

    public function test_organization_override_cannot_grant_platform_level_permission(): void
    {
        $organization = Organization::factory()->create();
        OrganizationPermissionOverride::factory()
            ->for($organization)
            ->forRole(Role::Viewer)
            ->grant(Permission::SensitiveOperationsExecute)
            ->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Permission [sensitive_operations.execute] cannot be granted by organization override.');

        app(RolePermissionResolver::class)->effectivePermissions(Role::Viewer, $organization);
    }

    public function test_unknown_role_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown role [contractor].');

        app(RolePermissionResolver::class)->baselinePermissions('contractor');
    }

    public function test_unknown_permission_override_is_rejected(): void
    {
        $organization = Organization::factory()->create();
        $organization->permissionOverrides()->create([
            'role' => Role::Member->value,
            'permission' => 'billing.manage',
            'effect' => 'grant',
        ]);

        $this->expectException(UnknownPermissionException::class);
        $this->expectExceptionMessage('Unknown permission [billing.manage].');

        app(RolePermissionResolver::class)->effectivePermissions(Role::Member, $organization);
    }

    public function test_permissions_for_membership_returns_effective_role_permissions(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $user->organizations()->attach($organization, ['role' => Role::Viewer->value]);

        $permissions = app(RolePermissionResolver::class)->permissionsForMembership($user, $organization);

        $this->assertContains(Permission::OrganizationsView, $permissions);
        $this->assertNotContains(Permission::OrganizationsUpdate, $permissions);
    }

    public function test_permissions_for_missing_membership_are_empty(): void
    {
        $permissions = app(RolePermissionResolver::class)->permissionsForMembership(
            User::factory()->create(),
            Organization::factory()->create(),
        );

        $this->assertSame([], $permissions);
    }
}
