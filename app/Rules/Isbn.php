<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Isbn implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! $this->checksumIsValid($value)) {
            $fail('The :attribute must be a valid ISBN-10 or ISBN-13 number.');
        }
    }

    /**
     * ISBN-10 and ISBN-13, with hyphens and spaces ignored.
     */
    public function checksumIsValid(string $value): bool
    {
        $normalized = strtoupper((string) preg_replace('/[ -]/', '', $value));

        if (preg_match('/^\d{9}[\dX]$/', $normalized) === 1) {
            return $this->isbn10Checksum($normalized);
        }

        if (preg_match('/^97[89]\d{10}$/', $normalized) === 1) {
            return $this->isbn13Checksum($normalized);
        }

        return false;
    }

    private function isbn10Checksum(string $isbn): bool
    {
        $sum = 0;

        for ($i = 0; $i < 9; $i++) {
            $sum += ((int) $isbn[$i]) * (10 - $i);
        }

        $check = $isbn[9] === 'X' ? 10 : (int) $isbn[9];

        return ($sum + $check) % 11 === 0;
    }

    private function isbn13Checksum(string $isbn): bool
    {
        $sum = 0;

        for ($i = 0; $i < 13; $i++) {
            $sum += ((int) $isbn[$i]) * ($i % 2 === 0 ? 1 : 3);
        }

        return $sum % 10 === 0;
    }
}
