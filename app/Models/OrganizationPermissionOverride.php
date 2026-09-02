<?php

namespace App\Models;

use Database\Factories\OrganizationPermissionOverrideFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['organization_id', 'role', 'permission', 'effect'])]
class OrganizationPermissionOverride extends Model
{
    /** @use HasFactory<OrganizationPermissionOverrideFactory> */
    use HasFactory;

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
