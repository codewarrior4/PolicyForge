<?php

namespace App\Authorization\Enums;

enum Role: string
{
    case Owner = 'owner';
    case Administrator = 'administrator';
    case Developer = 'developer';
    case Member = 'member';
    case Viewer = 'viewer';
}
