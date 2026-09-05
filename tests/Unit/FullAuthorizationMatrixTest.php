<?php

namespace Tests\Unit;

use App\Authorization\DTOs\AuthorizationContext;
use App\Authorization\DTOs\McpAuthorizationContext;
use App\Authorization\Enums\AuthorizationAction;
use App\Authorization\Enums\Permission;
use App\Authorization\Enums\Role;
use App\Authorization\Policies\McpToolPolicy;
use App\Authorization\Policies\OrganizationPolicy;
use App\Authorization\Services\RolePermissionResolver;
use App\Models\Organization;
use App\Models\OrganizationPermissionOverride;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FullAuthorizationMatrixTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{Role, Permission, bool}>
     */
    public static function rolePermissionCases(): array
    {
        return [
            'owner can delete organization' => [Role::Owner, Permission::OrganizationsDelete, true],
            'administrator can manage members' => [Role::Administrator, Permission::OrganizationsManageMembers, true],
            'developer can execute mcp' => [Role::Developer, Permission::McpExecute, true],
            'member cannot update users' => [Role::Member, Permission::UsersUpdate, false],
            'viewer cannot execute mcp' => [Role::Viewer, Permission::McpExecute, false],
        ];
    }

    #[DataProvider('rolePermissionCases')]
    public function test_role_permission_matrix_has_intentional_results(
        Role $role,
        Permission $permission,
        bool $expected,
    ): void {
        $actual = app(RolePermissionResolver::class)->allows($role, $permission);

        $this->assertSame($expected, $actual);
    }

    public function test_wrong_organization_denies_before_feature_or_resource_state(): void
    {
        $response = app(OrganizationPolicy::class)->view(
            new User(['email' => 'outsider@example.com']),
            new AuthorizationContext(
                principal: null,
                action: AuthorizationAction::View,
                feature: 'audit-log',
                metadata: [
                    'organization_matches' => false,
                    'feature_enabled' => true,
                    'resource_active' => true,
                ],
            ),
        );

        $this->assertTrue($response->denied());
        $this->assertSame('tenant_access_denied', $response->code());
        $this->assertSame(404, $response->status());
    }

    public function test_feature_disabled_denies_after_permission_is_known(): void
    {
        $response = app(OrganizationPolicy::class)->view(
            new User(['email' => 'auditor@example.com']),
            new AuthorizationContext(
                principal: null,
                action: AuthorizationAction::View,
                feature: 'audit-log',
                metadata: [
                    'organization_matches' => true,
                    'feature_enabled' => false,
                    'resource_active' => true,
                ],
            ),
        );

        $this->assertTrue($response->denied());
        $this->assertSame('feature_disabled', $response->code());
    }

    public function test_inactive_resource_denies_after_permission_and_feature_pass(): void
    {
        $response = app(OrganizationPolicy::class)->view(
            new User(['email' => 'auditor@example.com']),
            new AuthorizationContext(
                principal: null,
                action: AuthorizationAction::View,
                feature: 'audit-log',
                metadata: [
                    'organization_matches' => true,
                    'feature_enabled' => true,
                    'resource_active' => false,
                ],
            ),
        );

        $this->assertTrue($response->denied());
        $this->assertSame('resource_inactive', $response->code());
    }

    public function test_mcp_matrix_denies_revoked_identity_even_when_feature_is_enabled(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $user->organizations()->attach($organization, ['role' => Role::Developer->value]);
        OrganizationPermissionOverride::factory()
            ->for($organization)
            ->forRole(Role::Developer)
            ->revoke(Permission::McpExecute)
            ->create();

        $response = app(McpToolPolicy::class)->execute(new McpAuthorizationContext(
            user: $user,
            organization: $organization,
            tool: 'policy.search',
            feature: 'mcp',
            metadata: ['feature_enabled' => true],
        ));

        $this->assertTrue($response->denied());
        $this->assertSame('missing_permission', $response->code());
    }
}
