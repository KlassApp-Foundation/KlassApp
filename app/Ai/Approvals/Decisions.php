<?php

namespace App\Ai\Approvals;

use Illuminate\Support\Collection;

/**
 * Polyfill of Laravel\Ai\Approvals\Decisions (laravel/ai ^0.10).
 *
 * @extends Collection<string, Decision|bool>
 */
class Decisions extends Collection
{
    /**
     * @param  array<string, Decision|bool>  $items
     */
    public static function from(array $items): self
    {
        $normalized = [];
        foreach ($items as $id => $decision) {
            $normalized[$id] = match (true) {
                $decision instanceof Decision => $decision,
                $decision === true => Decision::approve(),
                $decision === false => Decision::reject(),
                default => throw new \InvalidArgumentException('Invalid decision for '.$id),
            };
        }

        return new self($normalized);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return parent::get($key, $default);
    }
}
