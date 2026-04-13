<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CrmSetting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'type'];

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return $setting->castValue();
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, mixed $value): void
    {
        $setting = static::where('key', $key)->first();

        if ($setting) {
            // Convert value to storable string
            $storedValue = match ($setting->type) {
                'boolean' => $value ? 'true' : 'false',
                'json'    => is_string($value) ? $value : json_encode($value),
                default   => (string) $value,
            };

            $setting->update(['value' => $storedValue]);
        } else {
            static::create([
                'key'   => $key,
                'value' => (string) $value,
                'group' => 'general',
                'type'  => 'string',
            ]);
        }

        Cache::forget('crm_settings');
    }

    /**
     * Get all settings grouped by group.
     */
    public static function allGrouped(): array
    {
        $settings = static::all();

        $grouped = [];
        foreach ($settings as $setting) {
            $grouped[$setting->group][$setting->key] = $setting->castValue();
        }

        return $grouped;
    }

    /**
     * Get all settings as flat key-value.
     */
    public static function allFlat(): array
    {
        return static::all()->mapWithKeys(function ($setting) {
            return [$setting->key => $setting->castValue()];
        })->toArray();
    }

    /**
     * Cast the stored string value to the appropriate PHP type.
     */
    public function castValue(): mixed
    {
        return match ($this->type) {
            'number'  => is_numeric($this->value) ? (int) $this->value : 0,
            'boolean' => $this->value === 'true' || $this->value === '1',
            'json'    => json_decode($this->value, true) ?? [],
            default   => $this->value,
        };
    }
}
