<?php

namespace Tests\Unit;

use App\Authorization\DTOs\McpAuthorizationContext;
use App\Authorization\Enums\Permission;
use App\Authorization\Enums\Role;
use App\Authorization\Policies\McpToolPolicy;
use App\Models\Organization;
use App\Models\OrganizationPermissionOverride;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McpAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_and_enabled_mcp_tool_can_execute(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $user->organizations()->attach($organization, ['role' => Role::Developer->value]);

        $response = app(McpToolPolicy::class)->execute(new McpAuthorizationContext(
            user: $user,
            organization: $organization,
            tool: 'policy.search',
            feature: 'mcp',
            metadata: ['feature_enabled' => true],
        ));

        $this->assertTrue($response->allowed());
    }

    public function test_authorized_but_disabled_mcp_tool_is_denied(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $user->organizations()->attach($organization, ['role' => Role::Developer->value]);

        $response = app(McpToolPolicy::class)->execute(new McpAuthorizationContext(
            user: $user,
            organization: $organization,
            tool: 'policy.search',
            feature: 'mcp',
            metadata: ['feature_enabled' => false],
        ));

        $this->assertTrue($response->denied());
        $this->assertSame('feature_disabled', $response->code());
    }

    public function test_enabled_mcp_tool_does_not_authorize_missing_permission(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $user->organizations()->attach($organization, ['role' => Role::Viewer->value]);
        OrganizationPermissionOverride::factory()
            ->for($organization)
            ->forRole(Role::Viewer)
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
