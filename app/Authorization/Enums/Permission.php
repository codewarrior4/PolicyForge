<?php

namespace App\Authorization\Enums;

enum Permission: string
{
    case UsersView = 'users.view';
    case UsersCreate = 'users.create';
    case UsersUpdate = 'users.update';
    case UsersDelete = 'users.delete';
    case UsersInvite = 'users.invite';
    case UsersDisable = 'users.disable';

    case OrganizationsView = 'organizations.view';
    case OrganizationsUpdate = 'organizations.update';
    case OrganizationsDelete = 'organizations.delete';
    case OrganizationsTransferOwnership = 'organizations.transfer_ownership';
    case OrganizationsManageMembers = 'organizations.manage_members';

    case RolesView = 'roles.view';
    case RolesAssign = 'roles.assign';
    case RolesRevoke = 'roles.revoke';

    case PermissionsView = 'permissions.view';
    case PermissionsGrant = 'permissions.grant';
    case PermissionsRevoke = 'permissions.revoke';

    case PasskeysView = 'passkeys.view';
    case PasskeysRegister = 'passkeys.register';
    case PasskeysRevoke = 'passkeys.revoke';

    case AuditView = 'audit.view';
    case AuditExport = 'audit.export';

    case McpExecute = 'mcp.execute';
    case McpAdmin = 'mcp.admin';
    case McpToolsView = 'mcp.tools.view';
    case McpToolsManage = 'mcp.tools.manage';

    case ApiKeysView = 'api_keys.view';
    case ApiKeysCreate = 'api_keys.create';
    case ApiKeysRevoke = 'api_keys.revoke';
    case ApiKeysRotate = 'api_keys.rotate';

    case SensitiveOperationsApprove = 'sensitive_operations.approve';
    case SensitiveOperationsExecute = 'sensitive_operations.execute';

    public function domain(): string
    {
        return explode('.', $this->value, 2)[0];
    }

    public function action(): string
    {
        return str($this->value)->afterLast('.')->value();
    }
}
