<?php

namespace App\Authorization\Policies;

use App\Authorization\DTOs\AuthorizationDecision;
use App\Authorization\Enums\AuthorizationAction;
use App\Authorization\Enums\Permission;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrganizationAccessPolicy
{
    public function access(User $user, Organization $organization): Response
    {
        if ($user->organizations()->whereKey($organization->getKey())->exists()) {
            return AuthorizationDecision::allow(
                action: AuthorizationAction::View,
                permission: Permission::OrganizationsView,
                metadata: ['organization_id' => $organization->getKey()],
            )->toLaravelResponse();
        }

        return AuthorizationDecision::deny(
            action: AuthorizationAction::View,
            reason: 'tenant_access_denied',
            permission: Permission::OrganizationsView,
            status: 404,
            metadata: ['organization_id' => $organization->getKey()],
        )->toLaravelResponse();
    }
}
