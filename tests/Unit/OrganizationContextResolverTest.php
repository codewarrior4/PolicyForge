<?php

namespace Tests\Unit;

use App\Authorization\Exceptions\InvalidAuthorizationContextException;
use App\Authorization\Exceptions\TenantAccessDeniedException;
use App\Authorization\Services\OrganizationContextResolver;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class OrganizationContextResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_organization_wins_over_requested_organization_id(): void
    {
        $user = User::factory()->create();
        $routeOrganization = Organization::factory()->create();
        $requestedOrganization = Organization::factory()->create();

        $request = Request::create('/organizations/'.$routeOrganization->getKey(), 'GET', [
            'organization_id' => $requestedOrganization->getKey(),
        ]);
        $request->setUserResolver(fn (): User => $user);
        $request->setRouteResolver(fn (): object => new class($routeOrganization)
        {
            public function __construct(private Organization $organization) {}

            public function parameter(string $key): ?Organization
            {
                return $key === 'organization' ? $this->organization : null;
            }
        });

        $context = app(OrganizationContextResolver::class)->resolve($request);

        $this->assertTrue($context->resolved());
        $this->assertTrue($routeOrganization->is($context->organization));
        $this->assertSame('route', $context->source);
        $this->assertSame($routeOrganization->getKey(), $context->requestedOrganizationId);
    }

    public function test_header_organization_resolves_when_user_is_a_member(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $user->organizations()->attach($organization, ['role' => 'member']);

        $request = Request::create('/documents', 'GET', server: [
            'HTTP_X_ORGANIZATION_ID' => $organization->getKey(),
        ]);
        $request->setUserResolver(fn (): User => $user);

        $context = app(OrganizationContextResolver::class)->resolve($request);

        $this->assertTrue($organization->is($context->organization));
        $this->assertSame('header', $context->source);
        $this->assertSame($organization->getKey(), $context->requestedOrganizationId);
    }

    public function test_requested_organization_is_rejected_when_user_is_not_a_member(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $request = Request::create('/documents', 'GET', [
            'organization_id' => $organization->getKey(),
        ]);
        $request->setUserResolver(fn (): User => $user);

        $this->expectException(TenantAccessDeniedException::class);

        app(OrganizationContextResolver::class)->resolve($request);
    }

    public function test_single_membership_becomes_the_organization_context(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $user->organizations()->attach($organization, ['role' => 'owner']);

        $request = Request::create('/documents');
        $request->setUserResolver(fn (): User => $user);

        $context = app(OrganizationContextResolver::class)->resolve($request);

        $this->assertTrue($organization->is($context->organization));
        $this->assertSame('single_membership', $context->source);
    }

    public function test_multiple_memberships_without_explicit_context_are_ambiguous(): void
    {
        $user = User::factory()->create();
        $user->organizations()->attach(Organization::factory()->count(2)->create());

        $request = Request::create('/documents');
        $request->setUserResolver(fn (): User => $user);

        $this->expectException(InvalidAuthorizationContextException::class);

        app(OrganizationContextResolver::class)->resolve($request);
    }

    public function test_request_without_principal_or_organization_stays_unresolved(): void
    {
        $context = app(OrganizationContextResolver::class)->resolve(Request::create('/documents'));

        $this->assertFalse($context->resolved());
        $this->assertNull($context->organization);
        $this->assertSame('unresolved', $context->source);
    }
}
