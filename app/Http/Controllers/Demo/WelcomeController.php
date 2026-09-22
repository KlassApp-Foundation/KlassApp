<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Demo;

use App\Http\Resources\Demo\Teacher as TeacherResource;
use App\Http\Resources\Demo\School as SchoolResource;
use App\Http\Resources\Demo\User as UserResource;
use App\Http\Controllers\Controller;
use App\Models\TeacherProfile;
use Illuminate\Http\Request;
use App\Helpers\SiteHelper;
use App\Models\School;
use App\Models\User;

class WelcomeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    /**
     * Public demo: only schools explicitly flagged for showcase, and only non-personal
     * fields. Previously this returned the first three active schools with their real
     * names, emails and phone numbers.
     */
    public function schoolList()
    {
        $schools = School::where('is_demo', true)
            ->where('status', 1)
            ->orderBy('id')
            ->take(3)
            ->get()
            ->map(fn ($school) => ['id' => $school->id, 'name' => $school->name])
            ->values()
            ->all();

        return ['data' => $schools];
    }

    /**
     * Public demo roster for one demo school. Returns names and roles only: never an email
     * (staff emails are their login identifiers) and never a phone number. Unknown or
     * non-demo ids are a clean 404 rather than an unauthenticated 500.
     */
    public function list($school_id)
    {
        $school = School::where('id', $school_id)
            ->where('status', 1)
            ->where('is_demo', true)
            ->first();

        abort_if($school === null, 404);

        $academicYear = SiteHelper::getAcademicYear($school->id);
        $yearId = $academicYear?->id;

        $principals = $yearId ? TeacherProfile::where([['school_id', $school->id], ['academic_year_id', $yearId], ['designation', 'principal']])->get() : collect();
        $teachers = $yearId ? TeacherProfile::where([['school_id', $school->id], ['academic_year_id', $yearId], ['designation', '!=', 'principal'], ['designation', '!=', 'librarian'], ['designation', '!=', 'receptionist'], ['designation', '!=', 'accountant']])->take(3)->get() : collect();

        $details = $school->getDetails();

        // Names and roles only. No email, no phone, nothing credential-shaped.
        $safe = function ($people, string $role) {
            return collect($people)->map(function ($person) use ($role) {
                $profile = $person->userprofile ?? null;
                $name = trim(($profile->firstname ?? '').' '.($profile->lastname ?? ''));

                return [
                    'fullname' => $name !== '' ? $name : (string) ($person->name ?? 'Member'),
                    'role' => $role,
                ];
            })->values()->all();
        };

        return [
            'admin' => $safe($details['admin'] ?? [], 'Administrator'),
            'principal' => $safe($principals, 'Principal'),
            'teacher' => $safe($teachers, 'Teacher'),
            'student' => $safe($details['student'] ?? [], 'Student'),
            'parent' => $safe($details['parent'] ?? [], 'Parent'),
            'librarian' => $safe($details['librarian'] ?? [], 'Librarian'),
            'receptionist' => $safe($details['receptionist'] ?? [], 'Receptionist'),
            'accountant' => $safe($details['accountant'] ?? [], 'Accountant'),
        ];
    }
}
