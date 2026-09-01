<?php

namespace Tests\Unit;

use App\Authorization\DTOs\AuthorizationContext;
use App\Authorization\Enums\AuthorizationAction;
use App\Authorization\Enums\Permission;
use App\Authorization\Policies\UserPolicy;
use App\Authorization\Services\AuthorizationService;
use App\Authorization\Services\PermissionRegistry;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserPolicyTest extends TestCase
{
    public function test_update_allows_resource_owner(): void
    {
        $policy = new UserPolicy($this->authorizationService());
        $user = new User(['email' => 'member@example.com']);
        $context = new AuthorizationContext(
            principal: null,
            action: AuthorizationAction::Update,
            resource: 'user:10',
            metadata: [
                'organization_matches' => true,
                'owns_resource' => true,
            ],
        );

        $response = $policy->update($user, $context);

        $this->assertTrue($response->allowed());
    }

    public function test_update_allows_delegated_manager(): void
    {
        $policy = new UserPolicy($this->authorizationService());
        $user = new User(['email' => 'admin@example.com']);
        $context = new AuthorizationContext(
            principal: null,
            action: AuthorizationAction::Update,
            resource: 'user:10',
            metadata: [
                'organization_matches' => true,
                'owns_resource' => false,
                'can_manage_resource' => true,
            ],
        );

        $response = $policy->update($user, $context);

        $this->assertTrue($response->allowed());
    }

    public function test_update_denies_non_owner_without_delegated_management(): void
    {
        $policy = new UserPolicy($this->authorizationService());
        $user = new User(['email' => 'member@example.com']);
        $context = new AuthorizationContext(
            principal: null,
            action: AuthorizationAction::Update,
            resource: 'user:10',
            metadata: [
                'organization_matches' => true,
                'owns_resource' => false,
                'can_manage_resource' => false,
            ],
        );

        $response = $policy->update($user, $context);

        $this->assertTrue($response->denied());
        $this->assertSame('resource_ownership_failed', $response->code());
    }

    public function test_view_denies_disabled_feature(): void
    {
        $policy = new UserPolicy($this->authorizationService());
        $user = new User(['email' => 'member@example.com']);
        $context = new AuthorizationContext(
            principal: null,
            action: AuthorizationAction::View,
            feature: 'user-management',
            metadata: ['feature_enabled' => false],
        );

        $response = $policy->view($user, $context);

        $this->assertTrue($response->denied());
        $this->assertSame('feature_disabled', $response->code());
    }

    private function authorizationService(): AuthorizationService
    {
        $registry = new PermissionRegistry;

        foreach (Permission::cases() as $permission) {
            $registry->register($permission);
        }

        return new AuthorizationService($registry);
    }
}
