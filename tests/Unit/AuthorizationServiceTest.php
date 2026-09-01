<?php

namespace Tests\Unit;

use App\Authorization\DTOs\AuthorizationContext;
use App\Authorization\Enums\AuthorizationAction;
use App\Authorization\Enums\Permission;
use App\Authorization\Exceptions\AuthorizationDeniedException;
use App\Authorization\Exceptions\InvalidAuthorizationContextException;
use App\Authorization\Exceptions\UnknownPermissionException;
use App\Authorization\Services\AuthorizationService;
use App\Authorization\Services\PermissionRegistry;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class AuthorizationServiceTest extends TestCase
{
    public function test_allows_registered_permission_for_authenticated_principal(): void
    {
        $service = new AuthorizationService(
            (new PermissionRegistry)->register(Permission::UsersView),
        );

        $decision = $service->inspect(new AuthorizationContext(
            principal: new User(['email' => 'viewer@example.com']),
            action: AuthorizationAction::View,
            permission: Permission::UsersView,
        ));

        $this->assertTrue($decision->allowed());
        $this->assertTrue($service->allows(new AuthorizationContext(
            principal: new User(['email' => 'viewer@example.com']),
            action: AuthorizationAction::View,
            permission: Permission::UsersView,
        )));
    }

    public function test_denies_unauthenticated_principal(): void
    {
        $service = new AuthorizationService(
            (new PermissionRegistry)->register(Permission::UsersView),
        );

        $decision = $service->inspect(new AuthorizationContext(
            principal: null,
            action: AuthorizationAction::View,
            permission: Permission::UsersView,
        ));

        $this->assertTrue($decision->denied());
        $this->assertSame('unauthenticated', $decision->reason);
        $this->assertSame(401, $decision->status);
    }

    public function test_denies_missing_permission(): void
    {
        $service = new AuthorizationService(new PermissionRegistry);

        $decision = $service->inspect(new AuthorizationContext(
            principal: new User(['email' => 'member@example.com']),
            action: AuthorizationAction::View,
        ));

        $this->assertTrue($decision->denied());
        $this->assertSame('missing_permission', $decision->reason);
        $this->assertSame(403, $decision->status);
    }

    public function test_rejects_empty_permission_context(): void
    {
        $service = new AuthorizationService(new PermissionRegistry);

        $this->expectException(InvalidAuthorizationContextException::class);
        $this->expectExceptionMessage('Authorization context is missing a permission.');

        $service->inspect(new AuthorizationContext(
            principal: new User(['email' => 'member@example.com']),
            action: AuthorizationAction::View,
            permission: '',
        ));
    }

    public function test_rejects_unknown_permission(): void
    {
        $service = new AuthorizationService(new PermissionRegistry);

        $this->expectException(UnknownPermissionException::class);
        $this->expectExceptionMessage('Unknown permission [audit.view].');

        $service->inspect(new AuthorizationContext(
            principal: new User(['email' => 'auditor@example.com']),
            action: AuthorizationAction::View,
            permission: Permission::AuditView,
        ));
    }

    public function test_denies_disabled_feature(): void
    {
        $service = new AuthorizationService(
            (new PermissionRegistry)->register(Permission::McpExecute),
        );

        $decision = $service->inspect(new AuthorizationContext(
            principal: new User(['email' => 'developer@example.com']),
            action: AuthorizationAction::Execute,
            permission: Permission::McpExecute,
            feature: 'mcp',
            metadata: ['feature_enabled' => false],
        ));

        $this->assertTrue($decision->denied());
        $this->assertSame('feature_disabled', $decision->reason);
    }

    public function test_authorize_throws_for_denied_decision(): void
    {
        $service = new AuthorizationService(new PermissionRegistry);

        $this->expectException(AuthorizationDeniedException::class);

        $service->authorize(new AuthorizationContext(
            principal: null,
            action: AuthorizationAction::View,
            permission: Permission::UsersView,
        ));
    }
}
