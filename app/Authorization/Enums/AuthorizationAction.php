<?php

namespace App\Authorization\Enums;

enum AuthorizationAction: string
{
    case View = 'view';
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
    case Execute = 'execute';
    case Manage = 'manage';
    case Register = 'register';
    case Invite = 'invite';
    case Disable = 'disable';
    case Assign = 'assign';
    case Revoke = 'revoke';
    case Grant = 'grant';
    case Rotate = 'rotate';
    case Approve = 'approve';
    case Export = 'export';
}
