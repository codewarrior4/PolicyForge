<?php

namespace App\Authorization\Enums;

enum SensitiveOperation: string
{
    case ChangeEmail = 'change_email';
    case ResetAuthentication = 'reset_authentication';
    case RevokeAllDevices = 'revoke_all_devices';
    case RotateApiKeys = 'rotate_api_keys';
    case TransferOrganizationOwnership = 'transfer_organization_ownership';
    case DeleteOrganization = 'delete_organization';
    case ExportCustomerData = 'export_customer_data';
    case ChangeBillingConfiguration = 'change_billing_configuration';

    public function permission(): Permission
    {
        return match ($this) {
            self::ChangeEmail,
            self::ResetAuthentication,
            self::RevokeAllDevices => Permission::SensitiveOperationsExecute,
            self::RotateApiKeys => Permission::ApiKeysRotate,
            self::TransferOrganizationOwnership => Permission::OrganizationsTransferOwnership,
            self::DeleteOrganization => Permission::OrganizationsDelete,
            self::ExportCustomerData => Permission::AuditExport,
            self::ChangeBillingConfiguration => Permission::SensitiveOperationsApprove,
        };
    }
}
