<?php

namespace App\Services\Records;

/**
 * sha256 of a snapshot payload. Object keys are sorted so two equal records
 * hash the same way. Lists keep their order. This is a content check, not a
 * signature: anyone who can rewrite the row can rewrite the hash too.
 */
class SnapshotHasher
{
    /**
     * @param  array<mixed>  $payload
     */
    public static function hash(array $payload): string
    {
        $encoded = json_encode(self::normalize($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return hash('sha256', $encoded === false ? '[]' : $encoded);
    }

    public static function normalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => self::normalize($item), $value);
        }

        ksort($value);

        foreach ($value as $key => $item) {
            $value[$key] = self::normalize($item);
        }

        return $value;
    }
}
