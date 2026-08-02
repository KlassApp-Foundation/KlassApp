<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPreference;
use InvalidArgumentException;

/**
 * Get/set typed Toshi user preferences for a resolved User only.
 *
 * Callers must pass the authenticated / effective User — never an arbitrary
 * user id from LLM tool arguments.
 */
class UserPreferenceService
{
    /** @var list<string> */
    public const UPDATABLE = [
        'preferred_language',
        'notification_channel',
        'digest_enabled',
        'digest_frequency',
        'digest_weekday',
        'timezone',
    ];

    /** @var list<string> */
    public const LANGUAGES = ['en', 'sw', 'lg', 'fr'];

    /** @var list<string> */
    public const CHANNELS = ['whatsapp', 'email', 'sms', 'none'];

    /** @var list<string> */
    public const FREQUENCIES = ['none', 'daily', 'weekly', 'monthly'];

    public function get(User $user): UserPreference
    {
        return UserPreference::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'preferred_language' => 'en',
                'notification_channel' => 'whatsapp',
                'digest_enabled' => false,
                'digest_frequency' => 'none',
                'digest_weekday' => null,
                'timezone' => null,
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(User $user, array $attributes): UserPreference
    {
        $payload = $this->validateAttributes($attributes);

        $preference = $this->get($user);
        $preference->fill($payload);
        $preference->save();

        return $preference->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(UserPreference $preference): array
    {
        return [
            'preferred_language' => $preference->preferred_language,
            'notification_channel' => $preference->notification_channel,
            'digest_enabled' => (bool) $preference->digest_enabled,
            'digest_frequency' => $preference->digest_frequency,
            'digest_weekday' => $preference->digest_weekday,
            'timezone' => $preference->timezone,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function validateAttributes(array $attributes): array
    {
        // Never accept targeting another user via LLM args.
        unset($attributes['user_id'], $attributes['id']);

        $payload = [];

        foreach (self::UPDATABLE as $key) {
            if (! array_key_exists($key, $attributes) || $attributes[$key] === null) {
                continue;
            }

            $payload[$key] = $attributes[$key];
        }

        if ($payload === []) {
            throw new InvalidArgumentException('No preference fields provided to update.');
        }

        if (isset($payload['preferred_language'])) {
            $lang = strtolower((string) $payload['preferred_language']);
            if (! in_array($lang, self::LANGUAGES, true)) {
                throw new InvalidArgumentException(
                    'preferred_language must be one of: '.implode(', ', self::LANGUAGES)
                );
            }
            $payload['preferred_language'] = $lang;
        }

        if (isset($payload['notification_channel'])) {
            $channel = strtolower((string) $payload['notification_channel']);
            if (! in_array($channel, self::CHANNELS, true)) {
                throw new InvalidArgumentException(
                    'notification_channel must be one of: '.implode(', ', self::CHANNELS)
                );
            }
            $payload['notification_channel'] = $channel;
        }

        if (isset($payload['digest_frequency'])) {
            $freq = strtolower((string) $payload['digest_frequency']);
            if (! in_array($freq, self::FREQUENCIES, true)) {
                throw new InvalidArgumentException(
                    'digest_frequency must be one of: '.implode(', ', self::FREQUENCIES)
                );
            }
            $payload['digest_frequency'] = $freq;
        }

        if (array_key_exists('digest_enabled', $payload)) {
            $payload['digest_enabled'] = filter_var($payload['digest_enabled'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($payload['digest_enabled'] === null) {
                throw new InvalidArgumentException('digest_enabled must be a boolean.');
            }
        }

        if (array_key_exists('digest_weekday', $payload)) {
            if ($payload['digest_weekday'] === '' || $payload['digest_weekday'] === null) {
                $payload['digest_weekday'] = null;
            } else {
                $weekday = (int) $payload['digest_weekday'];
                if ($weekday < 0 || $weekday > 6) {
                    throw new InvalidArgumentException('digest_weekday must be 0–6 (Sun–Sat) or null.');
                }
                $payload['digest_weekday'] = $weekday;
            }
        }

        if (isset($payload['timezone'])) {
            $tz = trim((string) $payload['timezone']);
            if ($tz === '') {
                $payload['timezone'] = null;
            } elseif (strlen($tz) > 64) {
                throw new InvalidArgumentException('timezone must be 64 characters or fewer.');
            } else {
                $payload['timezone'] = $tz;
            }
        }

        return $payload;
    }
}
