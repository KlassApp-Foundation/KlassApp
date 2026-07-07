<?php

namespace App\Services;

use App\Models\SchoolFeatureToggle;

class FeatureToggleService
{
    /**
     * Available feature keys.
     */
    public static function availableFeatures(): array
    {
        return [
            'toshi'      => 'Toshi AI Onboarding',
            'whatsapp'   => 'WhatsApp Integration',
            'schoolpay'  => 'School Pay Payments',
        ];
    }

    /**
     * Check if a feature is enabled for a given school.
     * Features default to disabled if no toggle record exists.
     */
    public static function isEnabled(int $schoolId, string $feature): bool
    {
        $toggle = SchoolFeatureToggle::where('school_id', $schoolId)
            ->where('feature', $feature)
            ->first();

        return $toggle ? $toggle->enabled : false;
    }

    /**
     * Enable or disable a feature for a given school.
     */
    public static function setEnabled(int $schoolId, string $feature, bool $enabled): array
    {
        if (!array_key_exists($feature, self::availableFeatures())) {
            return [
                'success' => false,
                'message' => "Unknown feature: {$feature}",
            ];
        }

        SchoolFeatureToggle::updateOrCreate(
            ['school_id' => $schoolId, 'feature' => $feature],
            ['enabled' => $enabled]
        );

        return [
            'success' => true,
            'message' => $enabled
                ? ucfirst($feature) . ' enabled for this school.'
                : ucfirst($feature) . ' disabled for this school.',
        ];
    }

    /**
     * Get all toggle states for a given school.
     */
    public static function getForSchool(int $schoolId): array
    {
        $toggles = SchoolFeatureToggle::where('school_id', $schoolId)
            ->pluck('enabled', 'feature')
            ->toArray();

        $result = [];
        foreach (self::availableFeatures() as $key => $label) {
            $result[$key] = [
                'label'   => $label,
                'enabled' => $toggles[$key] ?? false,
            ];
        }

        return $result;
    }
}
