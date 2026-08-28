<?php

namespace Tests\Unit\Support;

use App\Support\UserProvisioning;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserProvisioningTest extends TestCase
{
    public function test_random_password_attributes_use_bcrypt_and_is_reset(): void
    {
        $attrs = UserProvisioning::randomPasswordAttributes();

        $this->assertArrayHasKey('password', $attrs);
        $this->assertArrayHasKey('is_reset', $attrs);
        $this->assertSame(1, $attrs['is_reset']);
        $this->assertFalse(Hash::check('password', $attrs['password']));
        $this->assertSame(60, strlen($attrs['password']));
    }

    public function test_random_password_credentials_plain_matches_hash(): void
    {
        $creds = UserProvisioning::randomPasswordCredentials();

        $this->assertSame(16, strlen($creds['plain']));
        $this->assertTrue(Hash::check($creds['plain'], $creds['password']));
        $this->assertSame(1, $creds['is_reset']);
    }

    public function test_plain_random_password_length_matches_engine(): void
    {
        $this->assertSame(16, strlen(UserProvisioning::plainRandomPassword()));
    }
}
