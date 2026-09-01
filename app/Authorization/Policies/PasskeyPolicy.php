<?php

namespace App\Authorization\Policies;

use App\Authorization\DTOs\AuthorizationContext;
use App\Authorization\Enums\Permission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PasskeyPolicy extends ContextPolicy
{
    public function view(User $user, AuthorizationContext $context): Response
    {
        return $this->inspect($user, $context, Permission::PasskeysView, requiresResourceOwnership: true);
    }

    public function create(User $user, AuthorizationContext $context): Response
    {
        return $this->inspect($user, $context, Permission::PasskeysRegister, requiresActiveResource: false);
    }

    public function update(User $user, AuthorizationContext $context): Response
    {
        return $this->inspect($user, $context, Permission::PasskeysRevoke, requiresResourceOwnership: true);
    }

    public function delete(User $user, AuthorizationContext $context): Response
    {
        return $this->inspect($user, $context, Permission::PasskeysRevoke, requiresResourceOwnership: true);
    }

    public function manage(User $user, AuthorizationContext $context): Response
    {
        return $this->inspect($user, $context, Permission::PasskeysRevoke);
    }
}
