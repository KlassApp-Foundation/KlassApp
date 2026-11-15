<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolDetail;
use App\Services\Schools\SchoolIdentity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * School details printed on report cards: the head teacher's name and the crest. School admin only
 * (the route sits behind the full-school-admin gate). Always the signed-in user's own school.
 */
class SchoolIdentityController extends Controller
{
    public function edit()
    {
        $schoolId = (int) Auth::user()->school_id;
        $school = \App\Models\School::query()->findOrFail($schoolId);
        $hasStoredCrest = trim((string) SchoolDetail::query()->where('school_id', $schoolId)->where('meta_key', 'school_logo')->latest('id')->value('meta_value')) !== ''
            && trim((string) SchoolDetail::query()->where('school_id', $schoolId)->where('meta_key', 'school_logo')->latest('id')->value('meta_value')) !== '-';

        return view('admin.settings.school-identity', [
            'school' => $school,
            'headTeacherName' => SchoolIdentity::headTeacherName($schoolId),
            'crestFound' => SchoolIdentity::crestPath($schoolId) !== null,
            'hasStoredCrest' => $hasStoredCrest,
        ]);
    }

    public function update(Request $request)
    {
        $schoolId = (int) Auth::user()->school_id;
        $data = $request->validate([
            'head_teacher_name' => ['nullable', 'string', 'max:120'],
            'crest' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $name = trim(strip_tags((string) ($data['head_teacher_name'] ?? '')));
        SchoolDetail::query()->updateOrCreate(
            ['school_id' => $schoolId, 'meta_key' => SchoolIdentity::HEAD_TEACHER_KEY],
            ['meta_value' => $name === '' ? '-' : $name],
        );

        $crestSaved = false;
        if ($request->hasFile('crest')) {
            // Same place the settings upload has always used: the default disk, in the school's folder.
            $slug = (string) Auth::user()->school->slug;
            $path = Storage::putFile($slug.'/school_logo', $request->file('crest'));
            if ($path === false || $path === '') {
                return back()->withErrors(['crest' => 'The crest could not be saved. Try again.']);
            }
            SchoolDetail::query()->updateOrCreate(
                ['school_id' => $schoolId, 'meta_key' => 'school_logo'],
                ['meta_value' => $path],
            );
            $crestSaved = true;
        }

        activity()->performedOn(\App\Models\School::query()->findOrFail($schoolId))->causedBy(Auth::user())->useLog('setup')
            ->withProperties(['school_id' => $schoolId, 'crest_saved' => $crestSaved, 'head_teacher_name_set' => $name !== ''])
            ->log('Updated the report card school details');

        return redirect()->route('admin.school-identity')->with('successmessage', 'School details for report cards saved.');
    }
}
