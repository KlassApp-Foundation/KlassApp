<?php

namespace Tests\Feature\Storage;

use App\Traits\Common;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Downloads read file contents from the default disk (or an explicit one),
 * not from a hardcoded s3 disk via a leftover env() call.
 */
class FileDownloadPerDiskTest extends TestCase
{
    private function common(): object
    {
        return new class {
            use Common;
        };
    }

    public function test_download_helper_reads_from_the_default_disk(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('docs/note.txt', 'hello-object-storage');

        $this->assertSame('hello-object-storage', $this->common()->getFilePathforDownload('docs/note.txt'));
    }

    public function test_download_helper_accepts_an_explicit_disk(): void
    {
        Storage::fake('uploads');
        Storage::disk('uploads')->put('media/clip.txt', 'explicit-disk-content');

        $this->assertSame(
            'explicit-disk-content',
            $this->common()->getFilePathforDownload('media/clip.txt', 'uploads')
        );
    }
}
