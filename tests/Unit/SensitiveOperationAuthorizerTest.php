<?php

namespace Tests\Unit;

use App\Authorization\DTOs\SensitiveOperationContext;
use App\Authorization\Enums\Role;
use App\Authorization\Enums\SensitiveOperation;
use App\Authorization\Services\SensitiveOperationAuthorizer;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SensitiveOperationAuthorizerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sensitive_operation_requires_step_up_after_normal_permission(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $user->organizations()->attach($organization, ['role' => Role::Owner->value]);

        $decision = app(SensitiveOperationAuthorizer::class)->inspect(new SensitiveOperationContext(
            user: $user,
            organization: $organization,
            operation: SensitiveOperation::DeleteOrganization,
            stepUpSatisfied: false,
        ));

        $this->assertTrue($decision->denied());
        $this->assertSame('step_up_required', $decision->reason);
    }

    public function test_sensitive_operation_allows_when_permission_and_step_up_are_present(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $user->organizations()->attach($organization, ['role' => Role::Owner->value]);

        $decision = app(SensitiveOperationAuthorizer::class)->inspect(new SensitiveOperationContext(
            user: $user,
            organization: $organization,
            operation: SensitiveOperation::DeleteOrganization,
            stepUpSatisfied: true,
        ));

        $this->assertTrue($decision->allowed());
    }

    public function test_step_up_does_not_bypass_missing_permission(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $user->organizations()->attach($organization, ['role' => Role::Viewer->value]);

        $decision = app(SensitiveOperationAuthorizer::class)->inspect(new SensitiveOperationContext(
            user: $user,
            organization: $organization,
            operation: SensitiveOperation::DeleteOrganization,
            stepUpSatisfied: true,
        ));

        $this->assertTrue($decision->denied());
        $this->assertSame('missing_permission', $decision->reason);
    }
}
