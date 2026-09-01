<?php

namespace Tests\Unit;

use App\Authorization\DTOs\AuthorizationContext;
use App\Authorization\Enums\AuthorizationAction;
use App\Authorization\Enums\Permission;
use App\Authorization\Services\PermissionRegistry;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationGateIntegrationTest extends TestCase
{
    public function test_policyforge_authorize_gate_returns_laravel_response(): void
    {
        $user = new User(['email' => 'owner@example.com']);
        $context = new AuthorizationContext(
            principal: null,
            action: AuthorizationAction::View,
            permission: Permission::UsersView,
        );

        $response = Gate::forUser($user)->inspect('policyforge.authorize', $context);

        $this->assertTrue($response->allowed());
    }

    public function test_permission_gate_uses_permission_name_as_ability(): void
    {
        $user = new User(['email' => 'developer@example.com']);
        $context = new AuthorizationContext(
            principal: null,
            action: AuthorizationAction::Execute,
            permission: null,
            feature: 'mcp',
        );

        $response = Gate::forUser($user)->inspect(Permission::McpExecute->value, $context);

        $this->assertTrue($response->allowed());
    }

    public function test_permission_registry_is_loaded_with_known_permissions(): void
    {
        $registry = $this->app->make(PermissionRegistry::class);

        $this->assertTrue($registry->has(Permission::OrganizationsCreate));
        $this->assertSame(
            ['domain' => 'organizations', 'action' => 'create'],
            $registry->metadata(Permission::OrganizationsCreate),
        );
    }

    public function test_permission_gate_denies_disabled_feature(): void
    {
        $user = new User(['email' => 'developer@example.com']);
        $context = new AuthorizationContext(
            principal: null,
            action: AuthorizationAction::Execute,
            permission: null,
            feature: 'mcp',
            metadata: ['feature_enabled' => false],
        );

        $response = Gate::forUser($user)->inspect(Permission::McpExecute->value, $context);

        $this->assertTrue($response->denied());
        $this->assertSame('feature_disabled', $response->code());
    }
}
