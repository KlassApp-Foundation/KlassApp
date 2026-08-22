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
            'school_id' => 1,
            'academic_year_id' => 1,
            'standard_id' => 1,
            'section_id' => 1,
            'status' => 1,
        ];
    }
}
