<?php

namespace App\Authorization\Services;

use App\Authorization\DTOs\AuthorizationDecision;
use App\Authorization\DTOs\ServiceIdentity;
use App\Authorization\Enums\AuthorizationAction;
use App\Authorization\Enums\Permission;

class ServiceAuthorizationService
{
    public function inspect(
        ServiceIdentity $service,
        Permission $permission,
        int|string|null $organizationId = null,
    ): AuthorizationDecision {
        if (! $this->hasPermission($service, $permission)) {
            return AuthorizationDecision::deny(
                action: AuthorizationAction::Execute,
                reason: 'service_permission_denied',
                permission: $permission,
            );
        }

        if ($organizationId !== null && ! $this->inScope($service, $organizationId)) {
            return AuthorizationDecision::deny(
                action: AuthorizationAction::Execute,
                reason: 'service_scope_denied',
                permission: $permission,
                status: 404,
            );
        }

        return AuthorizationDecision::allow(
            action: AuthorizationAction::Execute,
            permission: $permission,
            metadata: [
                'service' => $service->name,
                'organization_id' => $organizationId,
            ],
        );
    }

    private function hasPermission(ServiceIdentity $service, Permission $permission): bool
    {
        foreach ($service->permissions as $servicePermission) {
            if ($servicePermission instanceof Permission && $servicePermission === $permission) {
                return true;
            }

            if ($servicePermission === $permission->value) {
                return true;
            }
        }

        return false;
    }

    private function inScope(ServiceIdentity $service, int|string $organizationId): bool
    {
        if ($service->organizationScope === ['*']) {
            return true;
        }

        return in_array((string) $organizationId, array_map('strval', $service->organizationScope), true);
    }
}
