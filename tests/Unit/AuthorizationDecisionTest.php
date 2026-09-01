<?php

namespace Tests\Unit;

use App\Authorization\DTOs\AuthorizationDecision;
use App\Authorization\Enums\AuthorizationAction;
use App\Authorization\Enums\Permission;
use App\Authorization\Exceptions\AuthorizationDeniedException;
use PHPUnit\Framework\TestCase;

class AuthorizationDecisionTest extends TestCase
{
    public function test_allow_decision_represents_allowed_action(): void
    {
        $decision = AuthorizationDecision::allow(
            action: AuthorizationAction::View,
            permission: Permission::UsersView,
            metadata: ['permission' => 'users.view'],
        );

        $this->assertTrue($decision->allowed());
        $this->assertFalse($decision->denied());
        $this->assertSame(AuthorizationAction::View, $decision->action);
        $this->assertSame(Permission::UsersView, $decision->permission);
        $this->assertNull($decision->reason);
        $this->assertSame(200, $decision->status);
        $this->assertSame(['permission' => 'users.view'], $decision->metadata);
    }

    public function test_deny_decision_represents_denied_action(): void
    {
        $decision = AuthorizationDecision::deny(
            action: AuthorizationAction::Delete,
            reason: 'missing_permission',
            permission: Permission::OrganizationsDelete,
        );

        $this->assertFalse($decision->allowed());
        $this->assertTrue($decision->denied());
        $this->assertSame('missing_permission', $decision->reason);
        $this->assertSame(403, $decision->status);
    }

    public function test_authorize_returns_allowed_decision(): void
    {
        $decision = AuthorizationDecision::allow(AuthorizationAction::View, Permission::UsersView);

        $this->assertSame($decision, $decision->authorize());
    }

    public function test_authorize_throws_for_denied_decision(): void
    {
        $decision = AuthorizationDecision::deny(
            action: AuthorizationAction::Update,
            reason: 'resource_outside_organization',
            permission: Permission::UsersUpdate,
            status: 404,
        );

        $this->expectException(AuthorizationDeniedException::class);
        $this->expectExceptionMessage('This action is unauthorized.');

        $decision->authorize();
    }

    public function test_converts_decision_to_laravel_response(): void
    {
        $response = AuthorizationDecision::deny(
            action: AuthorizationAction::Update,
            reason: 'resource_outside_organization',
            permission: Permission::UsersUpdate,
            status: 404,
        )->toLaravelResponse();

        $this->assertTrue($response->denied());
        $this->assertSame('resource_outside_organization', $response->code());
        $this->assertSame(404, $response->status());
    }
}
