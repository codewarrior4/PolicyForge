<?php

namespace Tests\Unit;

use App\Authorization\Policies\OrganizationAccessPolicy;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationAccessPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_membership_allows_organization_access(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $user->organizations()->attach($organization, ['role' => 'member']);

        $response = app(OrganizationAccessPolicy::class)->access($user, $organization);

        $this->assertTrue($response->allowed());
    }

    public function test_non_membership_denies_with_not_found_status(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $response = app(OrganizationAccessPolicy::class)->access($user, $organization);

        $this->assertTrue($response->denied());
        $this->assertSame(404, $response->status());
        $this->assertSame('tenant_access_denied', $response->code());
    }
}
