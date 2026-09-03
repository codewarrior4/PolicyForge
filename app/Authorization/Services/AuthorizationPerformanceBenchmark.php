<?php

namespace App\Authorization\Services;

use App\Authorization\DTOs\AuthorizationContext;
use App\Authorization\Enums\AuthorizationAction;
use App\Authorization\Enums\Permission;
use App\Models\User;

class AuthorizationPerformanceBenchmark
{
    /**
     * @return array<string, array{allowed: bool, duration_microseconds: float}>
     */
    public function measure(AuthorizationService $authorizationService): array
    {
        $user = new User(['email' => 'benchmark@policyforge.test']);

        return [
            'no_authorization' => $this->time(fn (): bool => true),
            'policy' => $this->time(fn (): bool => $authorizationService->allows(new AuthorizationContext(
                principal: $user,
                action: AuthorizationAction::View,
                permission: Permission::OrganizationsView,
            ))),
            'policy_permission_feature' => $this->time(fn (): bool => $authorizationService->allows(new AuthorizationContext(
                principal: $user,
                action: AuthorizationAction::Execute,
                permission: Permission::McpExecute,
                feature: 'mcp',
                metadata: ['feature_enabled' => true],
            ))),
            'policy_permission_feature_denied' => $this->time(fn (): bool => $authorizationService->allows(new AuthorizationContext(
                principal: $user,
                action: AuthorizationAction::Execute,
                permission: Permission::McpExecute,
                feature: 'mcp',
                metadata: ['feature_enabled' => false],
            ))),
        ];
    }

    /**
     * @return array{allowed: bool, duration_microseconds: float}
     */
    private function time(callable $callback): array
    {
        $start = hrtime(true);
        $allowed = $callback();

        return [
            'allowed' => $allowed,
            'duration_microseconds' => (hrtime(true) - $start) / 1000,
        ];
    }
}
