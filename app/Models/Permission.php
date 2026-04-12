<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends SpatiePermission
{
    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class, 'menu_has_permissions', 'permission_id', 'menu_id');
    }
}
