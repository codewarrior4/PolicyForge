<?php

namespace Tests\Unit;

use App\Authorization\DTOs\AuthorizationContext;
use App\Authorization\Enums\AuthorizationAction;
use App\Authorization\Enums\Permission;
use App\Authorization\Policies\PasskeyPolicy;
use App\Authorization\Services\AuthorizationService;
use App\Authorization\Services\PermissionRegistry;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class PasskeyPolicyTest extends TestCase
{
    public function test_view_allows_owned_active_passkey(): void
    {
        $policy = new PasskeyPolicy($this->authorizationService());
        $user = new User(['email' => 'member@example.com']);
        $context = new AuthorizationContext(
            principal: null,
            action: AuthorizationAction::View,
            resource: 'passkey:10',
            metadata: [
                'organization_matches' => true,
                'owns_resource' => true,
                'resource_active' => true,
            ],
        );

        $response = $policy->view($user, $context);

        $this->assertTrue($response->allowed());
    }

    public function test_delete_denies_passkey_owned_by_another_principal(): void
    {
        $policy = new PasskeyPolicy($this->authorizationService());
        $user = new User(['email' => 'member@example.com']);
        $context = new AuthorizationContext(
            principal: null,
            action: AuthorizationAction::Delete,
            resource: 'passkey:10',
            metadata: [
                'organization_matches' => true,
                'owns_resource' => false,
            ],
        );

        $response = $policy->delete($user, $context);

        $this->assertTrue($response->denied());
        $this->assertSame('resource_ownership_failed', $response->code());
    }

    public function test_view_denies_inactive_passkey(): void
    {
        $policy = new PasskeyPolicy($this->authorizationService());
        $user = new User(['email' => 'member@example.com']);
        $context = new AuthorizationContext(
            principal: null,
            action: AuthorizationAction::View,
            resource: 'passkey:10',
            metadata: [
                'organization_matches' => true,
                'owns_resource' => true,
                'resource_active' => false,
            ],
        );

        $response = $policy->view($user, $context);

        $this->assertTrue($response->denied());
        $this->assertSame('resource_inactive', $response->code());
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
