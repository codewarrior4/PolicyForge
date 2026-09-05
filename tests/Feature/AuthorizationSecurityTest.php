<?php

namespace Tests\Feature;

use App\Authorization\DTOs\McpAuthorizationContext;
use App\Authorization\DTOs\SensitiveOperationContext;
use App\Authorization\Enums\Permission;
use App\Authorization\Enums\Role;
use App\Authorization\Enums\SensitiveOperation;
use App\Authorization\Policies\McpToolPolicy;
use App\Authorization\Services\RolePermissionResolver;
use App\Authorization\Services\SensitiveOperationAuthorizer;
use App\Authorization\Services\TenantScopedResourceResolver;
use App\Jobs\DeletePolicyDocumentJob;
use App\Models\Organization;
use App\Models\OrganizationPermissionOverride;
use App\Models\PolicyDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AuthorizationSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_id_substitution_cannot_resolve_foreign_policy_document(): void
    {
        $organization = Organization::factory()->create();
        $foreignDocument = PolicyDocument::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        $organization->policyDocuments()->whereKey($foreignDocument->getKey())->firstOrFail();
    }

    public function test_mass_assignment_cannot_set_user_id_on_policy_document(): void
    {
        $document = new PolicyDocument([
            'organization_id' => 1,
            'owner_id' => 1,
            'user_id' => 999,
        ]);

        $this->assertArrayNotHasKey('user_id', $document->getAttributes());
    }

    public function test_viewer_cannot_promote_itself_by_adding_platform_permission_override(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $user->organizations()->attach($organization, ['role' => Role::Viewer->value]);
        OrganizationPermissionOverride::factory()
            ->for($organization)
            ->forRole(Role::Viewer)
            ->grant(Permission::SensitiveOperationsExecute)
            ->create();

        $decision = app(SensitiveOperationAuthorizer::class)->inspect(new SensitiveOperationContext(
            user: $user,
            organization: $organization,
            operation: SensitiveOperation::ResetAuthentication,
            stepUpSatisfied: true,
        ));

        $this->assertTrue($decision->denied());
        $this->assertSame('missing_permission', $decision->reason);
    }

    public function test_disabled_mcp_tool_cannot_be_executed_by_authorized_user(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $user->organizations()->attach($organization, ['role' => Role::Developer->value]);

        $response = app(McpToolPolicy::class)->execute(new McpAuthorizationContext(
            user: $user,
            organization: $organization,
            tool: 'policy.export',
            feature: 'mcp',
            metadata: ['feature_enabled' => false],
        ));

        $this->assertTrue($response->denied());
        $this->assertSame('feature_disabled', $response->code());
    }

    public function test_replayed_queued_operation_stops_after_membership_is_removed(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $document = PolicyDocument::factory()->for($organization)->create();
        $user->organizations()->attach($organization, ['role' => Role::Owner->value]);
        $job = DeletePolicyDocumentJob::forPolicyDocument(
            principalId: $user->getKey(),
            organizationId: $organization->getKey(),
            policyDocumentId: $document->getKey(),
        );
        $user->organizations()->detach($organization);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Organization membership was not found.');

        $job->handle(app(RolePermissionResolver::class), app(TenantScopedResourceResolver::class));
    }
}
