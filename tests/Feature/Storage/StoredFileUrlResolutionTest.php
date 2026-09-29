<?php

namespace Tests\Feature\Storage;

use App\Traits\Common;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * getFilePath() must resolve per disk: local disks serve /storage URLs, public
 * object storage buckets serve their configured base URL, and private buckets
 * (Laravel Cloud default) hand out short lived signed URLs.
 */
class StoredFileUrlResolutionTest extends TestCase
{
    private function common(): object
    {
        return new class {
            use Common;
        };
    }

    public function test_local_default_disk_returns_a_local_url(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('avatars/a.jpg', 'x');

        $url = $this->common()->getFilePath('avatars/a.jpg');

        $this->assertStringContainsString('avatars/a.jpg', $url);
        $this->assertStringNotContainsString('X-Amz', $url);
    }

    public function test_public_object_storage_bucket_serves_its_public_url(): void
    {
        config([
            'filesystems.default' => 's3',
            'filesystems.disks.s3.url' => 'https://cdn.example.test',
            'filesystems.disks.s3.key' => 'test-key',
            'filesystems.disks.s3.secret' => 'test-secret',
            'filesystems.disks.s3.region' => 'auto',
            'filesystems.disks.s3.bucket' => 'test-bucket',
            'filesystems.disks.s3.endpoint' => 'https://account.eu.r2.cloudflarestorage.com',
        ]);
        Storage::forgetDisk('s3');

        $url = $this->common()->getFilePath('avatars/a.jpg');

        $this->assertSame('https://cdn.example.test/avatars/a.jpg', $url);
    }

    public function test_private_object_storage_bucket_returns_a_signed_url(): void
    {
        config([
            'filesystems.default' => 's3',
            'filesystems.disks.s3.url' => null,
            'filesystems.disks.s3.key' => 'test-key',
            'filesystems.disks.s3.secret' => 'test-secret',
            'filesystems.disks.s3.region' => 'auto',
            'filesystems.disks.s3.bucket' => 'test-bucket',
            'filesystems.disks.s3.endpoint' => 'https://account.eu.r2.cloudflarestorage.com',
        ]);
        Storage::forgetDisk('s3');

        $url = $this->common()->getFilePath('avatars/a.jpg');

        $this->assertStringContainsString('avatars/a.jpg', $url);
        $this->assertStringContainsString('X-Amz-Signature', $url);
    }

    public function test_env_example_defaults_the_disk_to_local(): void
    {
        $example = file_get_contents(base_path('.env.example'));

        $this->assertNotFalse($example);
        $this->assertStringContainsString('FILESYSTEM_DISK=local', $example);
        $this->assertStringNotContainsString('FILESYSTEM_DRIVER=s3', $example);
    }
}
