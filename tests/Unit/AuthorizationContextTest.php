<?php

namespace Tests\Unit;

use App\Authorization\DTOs\AuthorizationContext;
use App\Authorization\Enums\AuthorizationAction;
use App\Authorization\Enums\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class AuthorizationContextTest extends TestCase
{
    public function test_stores_authorization_inputs_without_business_logic(): void
    {
        $principal = new User(['email' => 'admin@example.com']);
        $organization = (object) ['id' => 10];
        $resource = (object) ['id' => 20];
        $request = Request::create('/users/20', 'PATCH');

        $context = new AuthorizationContext(
            principal: $principal,
            action: AuthorizationAction::Update,
            permission: Permission::UsersUpdate,
            organization: $organization,
            resource: $resource,
            feature: 'user-management',
            request: $request,
            metadata: ['request_id' => 'req_123'],
        );

        $this->assertSame($principal, $context->principal);
        $this->assertSame(AuthorizationAction::Update, $context->action);
        $this->assertSame(Permission::UsersUpdate, $context->permission);
        $this->assertSame($organization, $context->organization);
        $this->assertSame($resource, $context->resource);
        $this->assertSame('user-management', $context->feature);
        $this->assertSame($request, $context->request);
        $this->assertSame(['request_id' => 'req_123'], $context->metadata);
    }

    public function test_allows_permission_names_for_dynamic_or_future_permissions(): void
    {
        $context = new AuthorizationContext(
            principal: null,
            action: AuthorizationAction::Execute,
            permission: 'mcp.tools.custom.execute',
        );

        $this->assertSame('mcp.tools.custom.execute', $context->permission);
    }
}
