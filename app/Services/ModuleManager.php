<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ModuleManager
{
    /**
     * Track whether the module_settings table exists to avoid repeated checks.
     */
    private static ?bool $tableExists = null;

    private static function tableExists(): bool
    {
        if (self::$tableExists === null) {
            try {
                self::$tableExists = Schema::hasTable('module_settings');
            } catch (\Throwable) {
                self::$tableExists = false;
            }
        }
        return self::$tableExists;
    }

    /**
     * Available modules in the system
     */
    const MODULES = [
        'derma' => [
            'slug' => 'derma',
            'default_name_ar' => 'الجلدية والتجميل',
            'default_name_en' => 'Dermatology & Cosmetics',
            'icon' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
            'color' => '#8B5CF6',
            'is_core' => true, // Cannot be disabled
        ],
        'dental' => [
            'slug' => 'dental',
            'default_name_ar' => 'طب الأسنان',
            'default_name_en' => 'Dentistry',
            'icon' => 'M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342',
            'color' => '#06B6D4',
            'is_core' => false,
        ],
        'hr' => [
            'slug' => 'hr',
            'default_name_ar' => 'الموارد البشرية',
            'default_name_en' => 'Human Resources',
            'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128H5.228A2 2 0 015 17.128c0-2.4 1.272-4.536 3.214-5.706M12 6.75a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zm8.25 2.25a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z',
            'color' => '#F59E0B',
            'is_core' => false,
        ],
        'inventory' => [
            'slug' => 'inventory',
            'default_name_ar' => 'المخزون',
            'default_name_en' => 'Inventory',
            'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
            'color' => '#6366F1',
            'is_core' => false,
        ],
        'insurance' => [
            'slug' => 'insurance',
            'default_name_ar' => 'التأمين',
            'default_name_en' => 'Insurance',
            'icon' => 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z',
            'color' => '#10B981',
            'is_core' => false,
        ],
        'pediatric' => [
            'slug' => 'pediatric',
            'default_name_ar' => 'طب الأطفال',
            'default_name_en' => 'Pediatrics',
            'icon' => 'M12 8.25a3.75 3.75 0 100-7.5 3.75 3.75 0 000 7.5zM6.75 12a.75.75 0 00-.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 00.75-.75v-.008a.75.75 0 00-.75-.75H6.75zm10.5 0a.75.75 0 00-.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 00.75-.75v-.008a.75.75 0 00-.75-.75h-.008zM12 10.5c-3.315 0-6 2.685-6 6v3a.75.75 0 00.75.75h10.5a.75.75 0 00.75-.75v-3c0-3.315-2.685-6-6-6z',
            'color' => '#4CAF50',
            'is_core' => false,
        ],
    ];

    /**
     * Get all registered modules
     */
    public static function getAllModules(): array
    {
        return self::MODULES;
    }

    /**
     * Check if a module is enabled
     */
    public static function isEnabled(string $module): bool
    {
        // Core modules are always enabled
        if (isset(self::MODULES[$module]) && self::MODULES[$module]['is_core']) {
            return true;
        }

        if (! self::tableExists()) {
            return false;
        }

        return Cache::remember("module_{$module}_enabled", 300, function () use ($module) {
            $setting = DB::table('module_settings')
                ->where('module', $module)
                ->where('key', 'enabled')
                ->value('value');

            return $setting === '1';
        });
    }

    /**
     * Get all active modules
     */
    public static function getActiveModules(): array
    {
        $active = [];
        foreach (self::MODULES as $slug => $module) {
            if (self::isEnabled($slug)) {
                $active[$slug] = array_merge($module, self::getModuleInfo($slug));
            }
        }
        return $active;
    }

    /**
     * Get module info with settings overrides
     */
    public static function getModuleInfo(string $module): array
    {
        $defaults = self::MODULES[$module] ?? [];

        if (! self::tableExists()) {
            return [
                'slug' => $module,
                'name_ar' => $defaults['default_name_ar'] ?? $module,
                'name_en' => $defaults['default_name_en'] ?? $module,
                'icon' => $defaults['icon'] ?? 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                'color' => $defaults['color'] ?? '#6B7280',
                'enabled' => $defaults['is_core'] ?? false,
                'is_core' => $defaults['is_core'] ?? false,
            ];
        }

        return Cache::remember("module_{$module}_info", 300, function () use ($module, $defaults) {
            $settings = DB::table('module_settings')
                ->where('module', $module)
                ->where('group', 'general')
                ->pluck('value', 'key')
                ->toArray();

            return [
                'slug' => $module,
                'name_ar' => $settings['name_ar'] ?? $defaults['default_name_ar'] ?? $module,
                'name_en' => $settings['name_en'] ?? $defaults['default_name_en'] ?? $module,
                'icon' => $settings['icon'] ?? $defaults['icon'] ?? 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                'color' => $settings['color'] ?? $defaults['color'] ?? '#6B7280',
                'enabled' => ($settings['enabled'] ?? '0') === '1' || ($defaults['is_core'] ?? false),
                'is_core' => $defaults['is_core'] ?? false,
            ];
        });
    }

    /**
     * Enable a module
     */
    public static function enable(string $module): bool
    {
        if (!isset(self::MODULES[$module])) {
            return false;
        }

        DB::table('module_settings')
            ->where('module', $module)
            ->where('key', 'enabled')
            ->update(['value' => '1']);

        self::clearCache($module);
        return true;
    }

    /**
     * Disable a module (cannot disable core modules)
     */
    public static function disable(string $module): bool
    {
        if (!isset(self::MODULES[$module]) || self::MODULES[$module]['is_core']) {
            return false;
        }

        DB::table('module_settings')
            ->where('module', $module)
            ->where('key', 'enabled')
            ->update(['value' => '0']);

        self::clearCache($module);
        return true;
    }

    /**
     * Get module settings grouped
     */
    public static function getSettings(string $module): array
    {
        if (! self::tableExists()) {
            return [];
        }

        $settings = DB::table('module_settings')
            ->where('module', $module)
            ->orderBy('group')
            ->orderBy('display_order')
            ->get();

        $grouped = [];
        foreach ($settings as $setting) {
            $grouped[$setting->group][] = $setting;
        }

        return $grouped;
    }

    /**
     * Get a specific module setting value
     */
    public static function getSetting(string $module, string $key, $default = null)
    {
        if (! self::tableExists()) {
            return $default;
        }

        return Cache::remember("module_{$module}_{$key}", 300, function () use ($module, $key, $default) {
            $value = DB::table('module_settings')
                ->where('module', $module)
                ->where('key', $key)
                ->value('value');

            return $value ?? $default;
        });
    }

    /**
     * Update a module setting
     */
    public static function setSetting(string $module, string $key, $value): void
    {
        DB::table('module_settings')
            ->where('module', $module)
            ->where('key', $key)
            ->update(['value' => $value, 'updated_at' => now()]);

        Cache::forget("module_{$module}_{$key}");
        self::clearCache($module);
    }

    /**
     * Bulk update module settings
     */
    public static function updateSettings(string $module, array $settings): void
    {
        foreach ($settings as $key => $value) {
            DB::table('module_settings')
                ->where('module', $module)
                ->where('key', $key)
                ->update(['value' => $value, 'updated_at' => now()]);
        }

        self::clearCache($module);
    }

    /**
     * Get modules data for frontend (Inertia shared props)
     */
    public static function getForFrontend(): array
    {
        $modules = [];
        foreach (self::MODULES as $slug => $module) {
            $info = self::getModuleInfo($slug);
            $modules[$slug] = $info;
        }
        return $modules;
    }

    /**
     * Clear all caches for a module
     */
    public static function clearCache(?string $module = null): void
    {
        if ($module) {
            Cache::forget("module_{$module}_enabled");
            Cache::forget("module_{$module}_info");

            // Clear all known setting keys
            if (self::tableExists()) {
                $keys = DB::table('module_settings')
                    ->where('module', $module)
                    ->pluck('key');

                foreach ($keys as $key) {
                    Cache::forget("module_{$module}_{$key}");
                }
            }
        } else {
            // Clear all module caches
            foreach (array_keys(self::MODULES) as $mod) {
                self::clearCache($mod);
            }
        }
    }
}
