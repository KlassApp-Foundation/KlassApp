<?php

namespace Database\Factories;

use App\Models\StandardLink;
use Illuminate\Database\Eloquent\Factories\Factory;

class StandardLinkFactory extends Factory
{
    protected $model = StandardLink::class;

    public function definition()
    {
        return [
            'status' => 1,
            // add other defaults here if needed
        ];
    }
}
