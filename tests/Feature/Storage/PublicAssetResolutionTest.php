<?php

namespace Tests\Feature\Storage;

use App\Traits\Common;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Bundled assets that ship in public/ (default avatars, icons, banners) must
 * be served from public assets and never looked up on the storage disk.
 *
 * #892 moved the default disk to a private object-storage bucket where these
 * files do not exist, so getFilePath() handed out a signed URL to a missing
 * object and every default avatar broke.
 */
class PublicAssetResolutionTest extends TestCase
{
    private function common(): object
    {
        return new class {
            use Common;
        };
    }

    private function usePrivateBucket(): void
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
    }

    public function test_bundled_default_avatars_serve_from_public_assets(): void
    {
        $this->usePrivateBucket();

        $defaults = [
            'uploads/male.png',
            'uploads/female.png',
            'uploads/user/avatar/default-user.jpg',
            'uploads/user/avatar/default-user-1.jpg',
        ];

        foreach ($defaults as $path) {
            $this->assertFileExists(public_path($path), "fixture missing: $path");

            $url = $this->common()->getFilePath($path);

            $this->assertStringEndsWith($path, $url, $path);
            $this->assertStringNotContainsString('X-Amz', $url, $path);
            $this->assertStringNotContainsString('r2.cloudflarestorage.com', $url, $path);
        }
    }

    /**
     * Old signups were written with uploads/images.jpg — an asset that was
     * never committed, so it exists in neither the bucket nor public/. It must
     * still render a working default rather than a dead signed URL.
     */
    public function test_orphaned_default_avatar_falls_back_to_a_bundled_asset(): void
    {
        $this->usePrivateBucket();

        $url = $this->common()->getFilePath('uploads/images.jpg');

        $this->assertStringEndsWith('uploads/user/avatar/default-user.jpg', $url);
        $this->assertStringNotContainsString('X-Amz', $url);
        $this->assertFileExists(public_path('uploads/user/avatar/default-user.jpg'));
    }

    /** External avatar URLs (Google) are already display URLs. */
    public function test_absolute_url_avatar_is_returned_as_is(): void
    {
        $this->usePrivateBucket();

        $remote = 'https://lh3.googleusercontent.com/a/example=s96-c';

        $this->assertSame($remote, $this->common()->getFilePath($remote));
    }

    /**
     * Regression guard: a genuine upload lives in the bucket and must keep
     * resolving through object storage — the public-asset rule is not a
     * blanket bypass of the storage disk.
     */
    public function test_uploaded_file_still_resolves_through_object_storage(): void
    {
        $this->usePrivateBucket();

        $url = $this->common()->getFilePath('avatars/a.jpg');

        $this->assertStringContainsString('avatars/a.jpg', $url);
        $this->assertStringContainsString('X-Amz-Signature', $url);
    }

    public function test_every_default_avatar_path_referenced_in_code_exists_in_public(): void
    {
        // Paths written as defaults by the sign-up traits.
        $written = [
            'uploads/male.png',
            'uploads/female.png',
            'uploads/user/avatar/default-user.jpg',
            'uploads/user/avatar/default-user-1.jpg',
        ];

        foreach ($written as $path) {
            $this->assertFileExists(public_path($path), "$path is written as a default avatar but is not in public/");
        }
    }
}
