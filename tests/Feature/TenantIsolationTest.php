<?php

namespace Tests\Feature;

use App\Authorization\Services\TenantScopedResourceResolver;
use App\Models\Organization;
use App\Models\PolicyDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_resolver_returns_document_inside_the_active_organization(): void
    {
        $organization = Organization::factory()->create();
        $document = PolicyDocument::factory()->for($organization)->create();

        $resolvedDocument = app(TenantScopedResourceResolver::class)
            ->policyDocumentById($organization, $document->getKey());

        $this->assertTrue($document->is($resolvedDocument));
    }

    public function test_resource_resolver_rejects_id_substitution_across_organizations(): void
    {
        $organization = Organization::factory()->create();
        $foreignDocument = PolicyDocument::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        app(TenantScopedResourceResolver::class)
            ->policyDocumentById($organization, $foreignDocument->getKey());
    }

    public function test_resource_resolver_rejects_uuid_substitution_across_organizations(): void
    {
        $organization = Organization::factory()->create();
        $foreignDocument = PolicyDocument::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        app(TenantScopedResourceResolver::class)
            ->policyDocumentByUuid($organization, $foreignDocument->uuid);
    }

    public function test_scoped_route_binding_hides_policy_document_from_another_organization(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $foreignDocument = PolicyDocument::factory()->create();
        $user->organizations()->attach($organization, ['role' => 'member']);

        $this->registerScopedPolicyDocumentRoute();

        $this
            ->actingAs($user)
            ->getJson('/test-organizations/'.$organization->getKey().'/policy-documents/'.$foreignDocument->getKey())
            ->assertNotFound();
    }

    public function test_scoped_route_binding_returns_policy_document_from_the_same_organization(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $document = PolicyDocument::factory()->for($organization)->create();
        $user->organizations()->attach($organization, ['role' => 'member']);

        $this->registerScopedPolicyDocumentRoute();

        $this
            ->actingAs($user)
            ->getJson('/test-organizations/'.$organization->getKey().'/policy-documents/'.$document->getKey())
            ->assertOk()
            ->assertJson([
                'organization_id' => $organization->getKey(),
                'policy_document_id' => $document->getKey(),
            ]);
    }

    private function registerScopedPolicyDocumentRoute(): void
    {
        Route::middleware('web')->scopeBindings()->get(
            '/test-organizations/{organization}/policy-documents/{policyDocument}',
            fn (Request $request, Organization $organization, PolicyDocument $policyDocument): array => [
                'organization_id' => $organization->getKey(),
                'policy_document_id' => $policyDocument->getKey(),
            ],
        );
    }
}
