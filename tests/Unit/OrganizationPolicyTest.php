<?php

namespace Tests\Unit;

use App\Authorization\DTOs\AuthorizationContext;
use App\Authorization\Enums\AuthorizationAction;
use App\Authorization\Enums\Permission;
use App\Authorization\Policies\OrganizationPolicy;
use App\Authorization\Services\AuthorizationService;
use App\Authorization\Services\PermissionRegistry;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class OrganizationPolicyTest extends TestCase
{
    public function test_view_allows_matching_organization_with_permission(): void
    {
        $policy = new OrganizationPolicy($this->authorizationService());
        $user = new User(['email' => 'owner@example.com']);
        $context = new AuthorizationContext(
            principal: null,
            action: AuthorizationAction::View,
            organization: 1,
            resource: 'organization:1',
            metadata: ['organization_matches' => true],
        );

        $response = $policy->view($user, $context);

        $this->assertTrue($response->allowed());
    }

    public function test_create_does_not_require_existing_organization_match(): void
    {
        $policy = new OrganizationPolicy($this->authorizationService());
        $user = new User(['email' => 'founder@example.com']);
        $context = new AuthorizationContext(
            principal: null,
            action: AuthorizationAction::Create,
            metadata: ['organization_matches' => false],
        );

        $response = $policy->create($user, $context);

        $this->assertTrue($response->allowed());
    }

    public function test_delete_hides_organization_from_wrong_tenant(): void
    {
        $policy = new OrganizationPolicy($this->authorizationService());
        $user = new User(['email' => 'outsider@example.com']);
        $context = new AuthorizationContext(
            principal: null,
            action: AuthorizationAction::Delete,
            permission: Permission::OrganizationsDelete,
            organization: 1,
            resource: 'organization:2',
            metadata: ['organization_matches' => false],
        );

        $response = $policy->delete($user, $context);

        $this->assertTrue($response->denied());
        $this->assertSame('tenant_access_denied', $response->code());
        $this->assertSame(404, $response->status());
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
