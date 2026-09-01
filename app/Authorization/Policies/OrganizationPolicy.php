<?php

namespace App\Authorization\Policies;

use App\Authorization\DTOs\AuthorizationContext;
use App\Authorization\Enums\Permission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrganizationPolicy extends ContextPolicy
{
    public function view(User $user, AuthorizationContext $context): Response
    {
        return $this->inspect($user, $context, Permission::OrganizationsView);
    }

    public function create(User $user, AuthorizationContext $context): Response
    {
        return $this->inspect($user, $context, Permission::OrganizationsCreate, requiresOrganizationMatch: false, requiresActiveResource: false);
    }

    public function update(User $user, AuthorizationContext $context): Response
    {
        return $this->inspect($user, $context, Permission::OrganizationsUpdate);
    }

    public function delete(User $user, AuthorizationContext $context): Response
    {
        return $this->inspect($user, $context, Permission::OrganizationsDelete);
    }

    public function manage(User $user, AuthorizationContext $context): Response
    {
        return $this->inspect($user, $context, Permission::OrganizationsManageMembers);
    }
}
