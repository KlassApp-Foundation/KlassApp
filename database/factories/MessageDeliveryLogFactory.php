<?php

namespace Database\Factories;

use App\Models\MessageDeliveryLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageDeliveryLogFactory extends Factory
{
    protected $model = MessageDeliveryLog::class;

    public function definition(): array
    {
        return [
            'whatsapp_message_id' => $this->faker->uuid(),
            'phone'               => '+2567' . $this->faker->numerify('########'),
            'direction'           => 'outbound',
            'status'              => 'sent',
            'content_preview'     => $this->faker->sentence(),
        ];
    }

    public function inbound(): static
    {
        return $this->state(fn () => ['direction' => 'inbound']);
    }

    public function failed(): static
    {
        return $this->state(fn () => ['status' => 'failed']);
    }
}
