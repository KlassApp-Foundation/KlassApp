<?php

namespace Tests\Unit;

use App\Models\Userprofile;
use App\Presenters\UserprofilePresenter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NullDateOfBirthAgeTest extends TestCase
{
    #[Test]
    public function get_age_returns_null_when_date_of_birth_is_blank(): void
    {
        $profile = new Userprofile;
        $presenter = new UserprofilePresenter($profile);

        $this->assertNull($presenter->getAge(null));
        $this->assertNull($presenter->getAge(''));
    }

    #[Test]
    public function get_age_returns_integer_for_valid_date_of_birth(): void
    {
        $profile = new Userprofile;
        $presenter = new UserprofilePresenter($profile);

        $age = $presenter->getAge('2000-01-15');

        $this->assertIsInt($age);
        $this->assertGreaterThan(0, $age);
    }
}
