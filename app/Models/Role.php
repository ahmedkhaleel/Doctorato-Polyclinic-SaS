<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\LogsActivity;

class Role extends Model
{
    use LogsActivity;
    protected $fillable = [
        'name',
        'display_name_en',
        'display_name_ar',
        'permissions',
        'is_system',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_system' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];

        if (in_array('*', $permissions)) {
            return true;
        }

        return in_array($permission, $permissions);
    }

    /**
     * Get all available permissions from the config registry.
     */
    public static function getAllPermissions(): array
    {
        $modules = config('permissions.modules', []);
        $permissions = [];

        foreach ($modules as $module => $config) {
            foreach ($config['actions'] as $action) {
                $permissions[] = "{$module}.{$action}";
            }
        }

        return $permissions;
    }

    /**
     * Get the permission modules with their labels for the UI.
     */
    public static function getPermissionModules(): array
    {
        return config('permissions.modules', []);
    }
}
