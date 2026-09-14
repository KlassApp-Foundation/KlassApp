<?php

namespace Tests\Feature;

use Tests\TestCase;

class CloudObjectStorageConfigTest extends TestCase
{
    public function test_filesystems_config_prefers_disk_env_and_omits_s3_acl_visibility(): void
    {
        $source = file_get_contents(config_path('filesystems.php'));

        $this->assertStringContainsString(
            "env('FILESYSTEM_DISK', env('FILESYSTEM_DRIVER', 'local'))",
            $source
        );
        $this->assertStringContainsString(
            "env('AWS_ACCESS_KEY_ID', env('AWS_KEY'))",
            $source
        );
        $this->assertStringContainsString(
            "env('AWS_SECRET_ACCESS_KEY', env('AWS_SECRET'))",
            $source
        );

        $s3BlockStart = strpos($source, "'s3' => [");
        $this->assertNotFalse($s3BlockStart);
        $s3Block = substr($source, $s3BlockStart, 700);
        $this->assertStringNotContainsString("'visibility'", $s3Block);
    }
}
