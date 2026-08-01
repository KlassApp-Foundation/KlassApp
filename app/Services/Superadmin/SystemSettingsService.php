<?php

namespace App\Services\Superadmin;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Shared system settings mutators for Livewire SystemSettings and Toshi tools.
 * Keys match Batch A SystemSettings.save.
 */
class SystemSettingsService
{
    /** @var list<string> */
    public const KEYS = [
        'sitetitle',
        'sitename',
        'sitelogo',
        'favicon',
        'maintenance',
        'login_status',
        'register_status',
    ];

    /** Access-control / public-facing keys that warrant HITL approval. */
    public const ACCESS_KEYS = [
        'maintenance',
        'login_status',
        'register_status',
    ];

    /** Cosmetic / display-only keys (no HITL by default). */
    public const DISPLAY_KEYS = [
        'sitetitle',
        'sitename',
        'sitelogo',
        'favicon',
    ];

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        $values = [];
        foreach (self::KEYS as $key) {
            $setting = Setting::where('key', $key)->first();
            $values[$key] = $setting ? (string) $setting->value : '';
        }

        return $values;
    }

    /**
     * Persist one or more settings. Only provided keys are written.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>  Written key => value
     *
     * @throws ValidationException
     */
    public function save(array $data): array
    {
        $payload = array_intersect_key($data, array_flip(self::KEYS));

        if ($payload === []) {
            throw ValidationException::withMessages([
                'settings' => 'Provide at least one known setting key: '.implode(', ', self::KEYS),
            ]);
        }

        $rules = [];
        foreach (array_keys($payload) as $key) {
            $rules[$key] = ['nullable', 'string', 'max:255'];
        }

        $validated = Validator::make($payload, $rules)->validate();

        $written = [];
        foreach ($validated as $key => $value) {
            $stringValue = $value === null ? '' : (string) $value;
            $setting = Setting::firstOrNew(['key' => $key]);
            if (! $setting->exists) {
                $setting->name = $key;
                $setting->description = $key;
                $setting->field = 'text';
                $setting->active = 1;
            }
            $setting->value = $stringValue;
            $setting->save();
            $written[$key] = $stringValue;
        }

        if (array_key_exists('login_status', $written)) {
            Config::set('settings.login_status', $written['login_status']);
        }

        return $written;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function touchesAccessControls(array $data): bool
    {
        foreach (self::ACCESS_KEYS as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null) {
                return true;
            }
        }

        return false;
    }
}
