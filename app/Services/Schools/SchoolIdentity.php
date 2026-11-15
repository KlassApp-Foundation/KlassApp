<?php

namespace App\Services\Schools;

use App\Models\SchoolDetail;
use Illuminate\Support\Facades\Storage;

/**
 * What a school prints at the top and bottom of a report card that is not a mark: its crest and the
 * head teacher's name. Every lookup is by school id.
 *
 * Crest: the settings upload writes the file with Storage::putFile(), which uses the DEFAULT disk
 * (FILESYSTEM_DISK). The card used to read the "public" disk, so on a school whose default disk is not
 * "public" the saved crest was never found. This resolves the crest from the disk it was saved to,
 * then falls back to "public" for rows saved by older code, and finally to null so the card prints the
 * monogram of the school's initials. See docs/dev/uploads-and-deploys.md for why a saved file can
 * disappear after a deploy.
 */
final class SchoolIdentity
{
    public const HEAD_TEACHER_KEY = 'head_teacher_name';

    /**
     * A local file path for the school's crest that a PDF renderer can read, or null when there is no
     * usable crest (nothing saved, the file is gone, or it is not an image).
     */
    public static function crestPath(int $schoolId): ?string
    {
        $meta = SchoolDetail::query()
            ->where('school_id', $schoolId)
            ->where('meta_key', 'school_logo')
            ->latest('id')
            ->first();

        $stored = trim((string) ($meta?->meta_value ?? ''));
        if ($stored === '' || $stored === '-') {
            return null;
        }

        foreach (self::diskNames() as $name) {
            $path = self::localCopy($name, $stored, $schoolId);
            if ($path !== null && @getimagesize($path) !== false) {
                return $path;
            }
        }

        return null;
    }

    public static function headTeacherName(int $schoolId): ?string
    {
        $value = trim((string) SchoolDetail::query()
            ->where('school_id', $schoolId)
            ->where('meta_key', self::HEAD_TEACHER_KEY)
            ->latest('id')
            ->value('meta_value'));

        return $value === '' || $value === '-' ? null : $value;
    }

    /**
     * Everything a report card template needs from here.
     *
     * @return array{crest_path: ?string, head_teacher_name: ?string}
     */
    public static function forCard(int $schoolId): array
    {
        return [
            'crest_path' => self::crestPath($schoolId),
            'head_teacher_name' => self::headTeacherName($schoolId),
        ];
    }

    /** @return list<string> the disk the upload used first, then the one older code read */
    private static function diskNames(): array
    {
        return array_values(array_unique([(string) config('filesystems.default', 'local'), 'public']));
    }

    private static function localCopy(string $diskName, string $stored, int $schoolId): ?string
    {
        if (config("filesystems.disks.{$diskName}") === null) {
            return null;
        }

        try {
            $disk = Storage::disk($diskName);
            if (! $disk->exists($stored)) {
                return null;
            }
            if ((string) config("filesystems.disks.{$diskName}.driver") === 'local') {
                return $disk->path($stored);
            }

            // A remote disk (object storage): a PDF renderer needs a local file, so keep a copy in the
            // temp folder. It is rebuilt whenever it is missing.
            $extension = pathinfo($stored, PATHINFO_EXTENSION) ?: 'img';
            $copy = sys_get_temp_dir().'/crest-'.$schoolId.'-'.md5($diskName.'|'.$stored).'.'.$extension;
            if (! is_file($copy)) {
                $contents = $disk->get($stored);
                if ($contents === null || $contents === '' || file_put_contents($copy, $contents) === false) {
                    return null;
                }
            }

            return $copy;
        } catch (\Throwable) {
            return null;
        }
    }
}
