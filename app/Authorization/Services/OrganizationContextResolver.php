<?php

namespace App\Authorization\Services;

use App\Authorization\DTOs\OrganizationContext;
use App\Authorization\Enums\AuthorizationAction;
use App\Authorization\Enums\Permission;
use App\Authorization\Exceptions\InvalidAuthorizationContextException;
use App\Authorization\Exceptions\TenantAccessDeniedException;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;

class OrganizationContextResolver
{
    public function resolve(Request $request): OrganizationContext
    {
        $principal = $request->user();
        $routeOrganization = $request->route('organization');

        if ($routeOrganization instanceof Organization) {
            return new OrganizationContext(
                organization: $routeOrganization,
                principal: $principal,
                source: 'route',
                requestedOrganizationId: $routeOrganization->getKey(),
            );
        }

        $requestedOrganizationId = $this->requestedOrganizationId($request);

        if ($requestedOrganizationId !== null) {
            if (! $principal instanceof User) {
                throw TenantAccessDeniedException::outsideTenant(
                    action: AuthorizationAction::View,
                    permission: Permission::OrganizationsView,
                    metadata: ['requested_organization_id' => $requestedOrganizationId],
                );
            }

            $organization = $principal->organizations()->whereKey($requestedOrganizationId)->first();

            if (! $organization instanceof Organization) {
                throw TenantAccessDeniedException::outsideTenant(
                    action: AuthorizationAction::View,
                    permission: Permission::OrganizationsView,
                    metadata: ['requested_organization_id' => $requestedOrganizationId],
                );
            }

            return new OrganizationContext(
                organization: $organization,
                principal: $principal,
                source: 'header',
                requestedOrganizationId: $requestedOrganizationId,
            );
        }

        if ($principal instanceof User) {
            $organizations = $principal->organizations()->limit(2)->get();

            if ($organizations->count() === 1) {
                return new OrganizationContext(
                    organization: $organizations->first(),
                    principal: $principal,
                    source: 'single_membership',
                    requestedOrganizationId: $organizations->first()->getKey(),
                );
            }

            if ($organizations->count() > 1) {
                throw InvalidAuthorizationContextException::ambiguousOrganization();
            }
        }

        return new OrganizationContext(
            organization: null,
            principal: $principal,
            source: 'unresolved',
        );
    }

    private function requestedOrganizationId(Request $request): ?int
    {
        $value = $request->header('X-Organization-Id') ?? $request->query('organization_id');

        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
