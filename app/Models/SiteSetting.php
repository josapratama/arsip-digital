<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group', 'label', 'sort_order'];

    /**
     * Get a setting value by key, with optional default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        if (!$setting) return $default;

        return match($setting->type) {
            'json' => json_decode($setting->value, true) ?? $default,
            default => $setting->value ?? $default,
        };
    }

    public static function clearCache(): void
    {
        Cache::forget('site_settings_flat');
        Cache::forget('site_settings_all_grouped');
    }

    /**
     * Get all settings as flat map, cached for high performance.
     */
    public static function getAllCached(): array
    {
        return Cache::rememberForever('site_settings_flat', function () {
            return static::orderBy('sort_order')
                ->get()
                ->mapWithKeys(fn($s) => [
                    $s->key => $s->type === 'json'
                        ? json_decode($s->value, true)
                        : $s->value
                ])
                ->toArray();
        });
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? json_encode($value) : $value]
        );
        static::clearCache();
    }

    /**
     * Get all settings in a group as key=>value array.
     */
    public static function getGroup(string $group): array
    {
        return static::where('group', $group)
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn($s) => [$s->key => $s->value])
            ->toArray();
    }

    /**
     * Get all settings grouped by group name.
     */
    public static function getAllGrouped(): array
    {
        return static::orderBy('sort_order')
            ->get()
            ->groupBy('group')
            ->map(fn($items) => $items->mapWithKeys(fn($s) => [$s->key => [
                'value' => $s->value,
                'type'  => $s->type,
                'label' => $s->label,
                'id'    => $s->id,
            ]]))
            ->toArray();
    }
}
