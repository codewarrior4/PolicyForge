<?php

namespace Tests\Unit;

use App\Authorization\Enums\Permission;
use App\Authorization\Services\PermissionRegistry;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

class PermissionRegistryTest extends TestCase
{
    public function test_registers_valid_permission_with_metadata(): void
    {
        $registry = new PermissionRegistry;

        $registry->register(Permission::UsersView, [
            'description' => 'View users in the active organization.',
        ]);

        $this->assertTrue($registry->has(Permission::UsersView));
        $this->assertSame(['users.view'], $registry->names());
        $this->assertSame(
            ['description' => 'View users in the active organization.'],
            $registry->metadata(Permission::UsersView),
        );
    }

    public function test_rejects_invalid_permission_name(): void
    {
        $registry = new PermissionRegistry;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid permission name [Users View].');

        $registry->register('Users View');
    }

    public function test_rejects_duplicate_permission(): void
    {
        $registry = new PermissionRegistry;
        $registry->register(Permission::McpExecute);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Permission [mcp.execute] is already registered.');

        $registry->register(Permission::McpExecute);
    }

    public function test_returns_false_for_missing_permission(): void
    {
        $registry = new PermissionRegistry;

        $this->assertFalse($registry->has(Permission::AuditView));
    }

    public function test_rejects_metadata_lookup_for_missing_permission(): void
    {
        $registry = new PermissionRegistry;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Permission [audit.view] is not registered.');

        $registry->metadata(Permission::AuditView);
    }

    public function test_returns_all_registered_permissions_with_metadata(): void
    {
        $registry = new PermissionRegistry;

        $registry
            ->register(Permission::UsersView, ['domain' => 'users'])
            ->register(Permission::McpExecute, ['domain' => 'mcp']);

        $this->assertSame(
            [
                'users.view' => ['domain' => 'users'],
                'mcp.execute' => ['domain' => 'mcp'],
            ],
            $registry->all(),
        );
    }
}
