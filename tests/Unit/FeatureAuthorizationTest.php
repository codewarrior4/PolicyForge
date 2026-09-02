<?php

namespace Tests\Unit;

use App\Authorization\DTOs\AuthorizationContext;
use App\Authorization\Enums\AuthorizationAction;
use App\Authorization\Enums\Permission;
use App\Authorization\Policies\OrganizationPolicy;
use App\Authorization\Services\AuthorizationService;
use App\Authorization\Services\FeatureAvailabilityResolver;
use App\Authorization\Services\PermissionRegistry;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class FeatureAuthorizationTest extends TestCase
{
    public function test_authorized_context_allows_when_feature_is_enabled(): void
    {
        $decision = $this->authorizationService()->inspect(new AuthorizationContext(
            principal: new User(['email' => 'developer@example.com']),
            action: AuthorizationAction::Execute,
            permission: Permission::McpExecute,
            feature: 'mcp',
            metadata: ['feature_enabled' => true],
        ));

        $this->assertTrue($decision->allowed());
    }

    public function test_authorized_context_denies_when_feature_is_disabled(): void
    {
        $decision = $this->authorizationService()->inspect(new AuthorizationContext(
            principal: new User(['email' => 'developer@example.com']),
            action: AuthorizationAction::Execute,
            permission: Permission::McpExecute,
            feature: 'mcp',
            metadata: ['feature_enabled' => false],
        ));

        $this->assertTrue($decision->denied());
        $this->assertSame('feature_disabled', $decision->reason);
    }

    public function test_named_feature_map_can_disable_a_single_feature(): void
    {
        $decision = $this->authorizationService()->inspect(new AuthorizationContext(
            principal: new User(['email' => 'developer@example.com']),
            action: AuthorizationAction::Execute,
            permission: Permission::McpExecute,
            feature: 'mcp',
            metadata: ['features' => ['mcp' => false]],
        ));

        $this->assertTrue($decision->denied());
        $this->assertSame('feature_disabled', $decision->reason);
    }

    public function test_enabled_feature_does_not_authorize_unauthenticated_principal(): void
    {
        $decision = $this->authorizationService()->inspect(new AuthorizationContext(
            principal: null,
            action: AuthorizationAction::Execute,
            permission: Permission::McpExecute,
            feature: 'mcp',
            metadata: ['feature_enabled' => true],
        ));

        $this->assertTrue($decision->denied());
        $this->assertSame('unauthenticated', $decision->reason);
        $this->assertSame(401, $decision->status);
    }

    public function test_feature_denial_happens_before_resource_state_denial(): void
    {
        $policy = new OrganizationPolicy($this->authorizationService());
        $context = new AuthorizationContext(
            principal: null,
            action: AuthorizationAction::View,
            feature: 'audit-log',
            metadata: [
                'organization_matches' => true,
                'feature_enabled' => false,
                'resource_active' => false,
            ],
        );

        $response = $policy->view(new User(['email' => 'auditor@example.com']), $context);

        $this->assertTrue($response->denied());
        $this->assertSame('feature_disabled', $response->code());
    }

    private function authorizationService(): AuthorizationService
    {
        $registry = new PermissionRegistry;

        foreach (Permission::cases() as $permission) {
            $registry->register($permission);
        }

        return new AuthorizationService($registry, new FeatureAvailabilityResolver);
    }
}
