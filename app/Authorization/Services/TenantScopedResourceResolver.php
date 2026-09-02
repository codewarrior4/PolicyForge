<?php

namespace App\Authorization\Services;

use App\Models\Organization;
use App\Models\PolicyDocument;

class TenantScopedResourceResolver
{
    public function policyDocumentById(Organization $organization, int $policyDocumentId): PolicyDocument
    {
        return $organization->policyDocuments()->whereKey($policyDocumentId)->firstOrFail();
    }

    public function policyDocumentByUuid(Organization $organization, string $uuid): PolicyDocument
    {
        return $organization->policyDocuments()->where('uuid', $uuid)->firstOrFail();
    }
}
