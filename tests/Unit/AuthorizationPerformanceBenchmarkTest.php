<?php

namespace Tests\Unit;

use App\Authorization\Enums\Permission;
use App\Authorization\Services\AuthorizationPerformanceBenchmark;
use App\Authorization\Services\AuthorizationService;
use App\Authorization\Services\PermissionRegistry;
use PHPUnit\Framework\TestCase;

class AuthorizationPerformanceBenchmarkTest extends TestCase
{
    public function test_benchmark_reports_authorization_paths(): void
    {
        $registry = new PermissionRegistry;

        foreach (Permission::cases() as $permission) {
            $registry->register($permission);
        }

        $results = (new AuthorizationPerformanceBenchmark)->measure(new AuthorizationService($registry));

        $this->assertSame(
            ['no_authorization', 'policy', 'policy_permission_feature', 'policy_permission_feature_denied'],
            array_keys($results),
        );
        $this->assertTrue($results['policy']['allowed']);
        $this->assertFalse($results['policy_permission_feature_denied']['allowed']);
        $this->assertGreaterThanOrEqual(0, $results['policy']['duration_microseconds']);
    }
}
