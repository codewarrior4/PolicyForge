<?php

namespace Tests\Feature;

use App\Authorization\Enums\Permission;
use App\Authorization\Enums\Role;
use App\Authorization\Services\RolePermissionResolver;
use App\Authorization\Services\TenantScopedResourceResolver;
use App\Jobs\DeletePolicyDocumentJob;
use App\Models\Organization;
use App\Models\OrganizationPermissionOverride;
use App\Models\PolicyDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class QueueAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_queued_job_deletes_resource_when_authorization_still_holds(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $document = PolicyDocument::factory()->for($organization)->create();
        $user->organizations()->attach($organization, ['role' => Role::Owner->value]);

        DeletePolicyDocumentJob::forPolicyDocument(
            principalId: $user->getKey(),
            organizationId: $organization->getKey(),
            policyDocumentId: $document->getKey(),
        )->handle(app(RolePermissionResolver::class), app(TenantScopedResourceResolver::class));

        $this->assertModelMissing($document);
    }

    public function test_queued_job_rechecks_permission_revoked_after_dispatch(): void
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
        OrganizationPermissionOverride::factory()
            ->for($organization)
            ->forRole(Role::Owner)
            ->revoke(Permission::OrganizationsDelete)
            ->create();

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Queued action is no longer authorized.');

        $job->handle(app(RolePermissionResolver::class), app(TenantScopedResourceResolver::class));
    }
}
