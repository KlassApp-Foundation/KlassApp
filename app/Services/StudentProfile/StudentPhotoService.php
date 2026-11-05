<?php

namespace App\Services\StudentProfile;

use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use App\Traits\Common;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * A student's photo: one JPG or PNG up to 2 MB, kept with the school's other student avatars
 * ("{school slug}/student/avatar", the folder the student form already uses) and recorded on the
 * userprofile. A new photo replaces the old file; removing one clears it and the initials show again.
 */
class StudentPhotoService
{
    use AuthorizesStudentProfileActions;
    use Common;

    public const MAX_KB = 2048;

    public function set(User $actor, User $student, ?UploadedFile $file): void
    {
        $this->authorizeProfileAction($actor, $student);

        if ($file === null || ! $file->isValid()) {
            throw ValidationException::withMessages(['photo' => 'Choose a JPG or PNG photo of 2 MB or less.']);
        }
        if ($file->getSize() > self::MAX_KB * 1024) {
            throw ValidationException::withMessages(['photo' => 'That photo is larger than 2 MB. Choose a smaller one.']);
        }
        $mime = (string) $file->getMimeType();
        if (! in_array($mime, ['image/jpeg', 'image/png'], true) || ! in_array(strtolower((string) $file->guessExtension()), ['jpg', 'jpeg', 'png'], true)) {
            throw ValidationException::withMessages(['photo' => 'Photos must be a JPG or PNG file.']);
        }

        $profile = $this->profile($student);
        $school = School::query()->findOrFail($student->school_id);
        $path = $this->uploadFile($school->slug.'/student/avatar', $file);
        if ($path === '' || $path === false) {
            throw ValidationException::withMessages(['photo' => 'The photo could not be saved. Try again.']);
        }

        $old = (string) $profile->avatar;
        $profile->avatar = $path;
        $profile->save();
        $this->discard($old, $school);

        activity('students')->causedBy($actor)->performedOn($student)->log('student.photo_changed');
    }

    public function remove(User $actor, User $student): void
    {
        $this->authorizeProfileAction($actor, $student);

        $profile = $this->profile($student);
        $school = School::query()->findOrFail($student->school_id);
        $old = (string) $profile->avatar;
        $profile->avatar = null;
        $profile->save();
        $this->discard($old, $school);

        activity('students')->causedBy($actor)->performedOn($student)->log('student.photo_removed');
    }

    /** True when the student has an uploaded photo (not the bundled placeholder). */
    public static function hasPhoto(?Userprofile $profile): bool
    {
        $avatar = (string) ($profile?->avatar ?? '');

        return $avatar !== '' && $avatar !== 'uploads/images.jpg';
    }

    private function profile(User $student): Userprofile
    {
        $profile = Userprofile::query()
            ->where('school_id', $student->school_id)
            ->where('user_id', $student->id)
            ->first();
        abort_unless($profile, 404);

        return $profile;
    }

    /** Delete the old file, but only a file this school's student folder owns. */
    private function discard(string $path, School $school): void
    {
        if ($path === '' || ! str_starts_with($path, $school->slug.'/student/avatar/')) {
            return;
        }
        try {
            Storage::delete($path);
        } catch (\Throwable $e) {
            Log::info($e->getMessage());
        }
    }
}
