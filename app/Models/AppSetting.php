<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

#[Fillable(['key', 'value', 'type', 'group'])]
class AppSetting extends Model
{
    private const CACHE_KEY = 'app_settings';

    private const CACHE_TTL = 3600;

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $settings = static::cached();

        return $settings[$key] ?? $default;
    }

    public static function setValue(string $key, mixed $value, string $type = 'string', string $group = 'branding'): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type, 'group' => $group],
        );
        static::clearCache();
    }

    public static function cached(): array
    {
        try {
            return Cache::remember(static::CACHE_KEY, static::CACHE_TTL, function (): array {
                if (! Schema::hasTable((new static)->getTable())) {
                    return [];
                }

                return static::query()->pluck('value', 'key')->all();
            });
        } catch (\Throwable) {
            return [];
        }
    }

    public static function clearCache(): void
    {
        Cache::forget(static::CACHE_KEY);
    }

    public static function branding(): array
    {
        $settings = static::cached();

        return [
            'app_name' => $settings['app_name'] ?? 'KPI Kepegawaian',
            'app_logo' => $settings['app_logo'] ?? null,
            'app_favicon' => $settings['app_favicon'] ?? null,
            'app_og_image' => $settings['app_og_image'] ?? null,
            'primary_color' => $settings['primary_color'] ?? '#16A34A',
            'footer_text' => $settings['footer_text'] ?? '',
        ];
    }
}
