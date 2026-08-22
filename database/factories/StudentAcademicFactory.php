<?php

namespace Database\Factories;

use App\Models\StudentAcademic;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentAcademicFactory extends Factory
{
    protected $model = StudentAcademic::class;

    public function definition()
    {
        return [
            'school_id' => 1,
            'academic_year_id' => 1,
            'user_id' => 1,
            'standardLink_id' => 1,
        ];
    }
}
