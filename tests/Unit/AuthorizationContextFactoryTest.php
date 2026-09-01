<?php

namespace Tests\Unit;

use App\Authorization\Enums\AuthorizationAction;
use App\Authorization\Enums\Permission;
use App\Authorization\Services\AuthorizationContextFactory;
use App\Models\User;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class AuthorizationContextFactoryTest extends TestCase
{
    public function test_creates_context_from_request_user_and_explicit_inputs(): void
    {
        $principal = new User(['email' => 'owner@example.com']);
        $organization = (object) ['id' => 1];
        $resource = (object) ['id' => 2];
        $request = Request::create('/organizations/1/users/2', 'PATCH');
        $request->setUserResolver(fn (): User => $principal);

        $context = (new AuthorizationContextFactory)->fromRequest(
            request: $request,
            action: AuthorizationAction::Update,
            permission: Permission::UsersUpdate,
            organization: $organization,
            resource: $resource,
            feature: 'user-management',
            metadata: ['source' => 'http'],
        );

        $this->assertSame($principal, $context->principal);
        $this->assertSame(AuthorizationAction::Update, $context->action);
        $this->assertSame(Permission::UsersUpdate, $context->permission);
        $this->assertSame($organization, $context->organization);
        $this->assertSame($resource, $context->resource);
        $this->assertSame('user-management', $context->feature);
        $this->assertSame($request, $context->request);
        $this->assertSame(['source' => 'http'], $context->metadata);
    }

    public function test_creates_context_without_request_for_non_http_boundaries(): void
    {
        $context = (new AuthorizationContextFactory)->make(
            action: AuthorizationAction::Execute,
            permission: Permission::McpExecute,
            resource: 'mcp.tool.policy.scan',
            feature: 'mcp',
            metadata: ['source' => 'mcp'],
        );

        $this->assertNull($context->principal);
        $this->assertSame(AuthorizationAction::Execute, $context->action);
        $this->assertSame(Permission::McpExecute, $context->permission);
        $this->assertNull($context->organization);
        $this->assertSame('mcp.tool.policy.scan', $context->resource);
        $this->assertSame('mcp', $context->feature);
        $this->assertNull($context->request);
        $this->assertSame(['source' => 'mcp'], $context->metadata);
    }
}
