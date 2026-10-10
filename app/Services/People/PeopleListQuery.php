<?php

namespace App\Services\People;

use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\StandardLink;
use App\Models\StudentParentLink;
use App\Models\TeacherInvite;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\WhatsAppUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * School-scoped rows for the shared students, teachers and parents list.
 */
class PeopleListQuery
{
    private const PER_PAGE = 10;

    /**
     * @return array<string, mixed>
     */
    public function students(User $admin, Request $request): array
    {
        $schoolId = (int) $admin->school_id;
        $search = trim((string) $request->input('search', ''));
        $chip = $this->studentChip($request);
        $standardId = $this->sameSchoolStandardId($schoolId, $request->input('standard'));

        $query = $this->studentQuery($schoolId);
        $this->applyStudentSearch($query, $search);
        $this->applyStudentChip($query, $chip, $standardId, $schoolId, $request);

        $paginator = $query->with(['parents.userParent.userprofile', 'userprofile'])
            ->orderBy(
                \App\Models\Userprofile::query()->select('firstname')
                    ->whereColumn('user_id', 'users.id')
                    ->limit(1)
            )
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $rows = [];
        foreach ($paginator as $student) {
            $parent = $this->firstParent($student, $schoolId);
            $classLabel = $this->classLabel($student->class_name ?? null, $student->class_stream ?? null);
            $kls = trim((string) ($student->kls_number ?? ''));
            $rows[] = [
                'id' => $student->id,
                'name' => $this->personName($student),
                'profile_url' => url('/admin/student/show/'.$student->name),
                'user' => $student,
                'card' => array_values(array_filter([
                    $kls !== '' ? $kls : null,
                    $classLabel,
                    $student->status !== 'active' ? ucfirst((string) $student->status) : null,
                ])),
                'cells' => [
                    ['text' => $kls !== '' ? $kls : '—', 'class' => 'people-kls'],
                    $classLabel === null
                        ? ['text' => 'No class', 'badge' => 'warn']
                        : ['text' => $classLabel],
                    $parent === null
                        ? ['text' => 'No parent', 'badge' => 'warn']
                        : ['text' => $parent['name']],
                    ['text' => $student->status === 'active' ? 'Active' : 'Inactive', 'badge' => $student->status === 'active' ? 'ok' : 'off'],
                ],
                'menu' => [
                    ['label' => 'View profile', 'href' => url('/admin/student/show/'.$student->name)],
                    ['label' => 'Edit', 'href' => url('/admin/student/edit/'.$student->name)],
                    ['label' => 'Move to class', 'href' => url('/admin/student/edit/'.$student->name)],
                    ['label' => 'Message parent', 'href' => $parent ? url('/admin/parent/show/'.$parent['slug']) : url('/admin/parents')],
                ],
            ];
        }

        $counts = $this->studentCounts($schoolId);
        $selectedClass = $standardId ? $this->classOptionLabel($schoolId, $standardId) : null;

        return $this->envelope('students', 'Students', $admin, $search, $chip, $paginator, $rows, $counts['all'], [
            'placeholder' => 'Search by name, KLS number',
            'add_label' => 'Add student',
            'add_url' => url('/admin/student/add'),
            'import_url' => url('/admin/import'),
            'empty_copy' => 'Add students one by one, or import a spreadsheet with names and classes.',
            'columns' => ['KLS number', 'Class', 'Parent or guardian', 'Status'],
            'bulk' => [
                ['label' => 'Message parents', 'href' => url('/admin/whatsapp/parents')],
                ['label' => 'Move to class', 'href' => url('/admin/students')],
                ['label' => 'Export', 'href' => url('/admin/import')],
            ],
            'chips' => [
                $this->chip('all', 'All', $counts['all'], $chip, $request, '/admin/students'),
                $this->classChip($chip, $selectedClass, $this->classOptions($schoolId), $search, '/admin/students'),
                $this->chip('active', 'Active', $counts['active'], $chip, $request, '/admin/students'),
                $this->chip('inactive', 'Inactive', $counts['inactive'], $chip, $request, '/admin/students'),
                $this->chip('noclass', 'No class', $counts['noclass'], $chip, $request, '/admin/students'),
                $this->chip('noparent', 'No parent', $counts['noparent'], $chip, $request, '/admin/students'),
            ],
            'standard' => $standardId,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function teachers(User $admin, Request $request): array
    {
        $schoolId = (int) $admin->school_id;
        $search = trim((string) $request->input('search', ''));
        $chip = $this->allowedChip($request, ['all', 'active', 'not_invited', 'pending', 'class_teacher'], 'all');
        $yearId = $this->currentYearId($schoolId);

        $query = User::query()
            ->with(['userprofile', 'teacherprofile'])
            ->where('school_id', $schoolId)
            ->where('usergroup_id', 5)
            ->whereIn('status', ['active', 'inactive']);

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhereHas('userprofile', function (Builder $profile) use ($search) {
                        $profile->where('firstname', 'like', '%'.$search.'%')
                            ->orWhere('lastname', 'like', '%'.$search.'%');
                    });
            });
        }

        $this->applyTeacherChip($query, $chip, $schoolId, $yearId);

        $paginator = $query->orderBy('name')->paginate(self::PER_PAGE)->withQueryString();
        $ids = collect($paginator->items())->pluck('id')->all();
        $emails = collect($paginator->items())->pluck('email')->filter()->map(fn ($email) => strtolower((string) $email))->all();

        $subjects = $this->subjectsByTeacher($schoolId, $yearId, $ids);
        $classOf = $this->classTeacherLabels($schoolId, $yearId, $ids);
        $pendingEmails = $this->pendingInviteEmails($schoolId, $emails);

        $rows = [];
        foreach ($paginator as $teacher) {
            $email = strtolower((string) $teacher->email);
            $invite = $this->inviteLabel($teacher, isset($pendingEmails[$email]));
            $teaches = $subjects[$teacher->id] ?? [];
            $classes = $classOf[$teacher->id] ?? [];
            $role = $this->teacherRole($teacher);
            $rows[] = [
                'id' => $teacher->id,
                'name' => $this->personName($teacher),
                'role' => $role,
                'profile_url' => url('/admin/teacher/show/'.$teacher->name),
                'user' => $teacher,
                'card' => array_values(array_filter([
                    $classes !== [] ? 'Class teacher · '.implode(', ', $classes) : $role,
                    $invite !== 'Joined' ? $invite : null,
                ])),
                'cells' => [
                    ['text' => $teaches !== [] ? implode(', ', $teaches) : '—'],
                    ['text' => $classes !== [] ? implode(', ', $classes) : '—'],
                    ['text' => $invite, 'badge' => $invite === 'Joined' ? 'ok' : ($invite === 'Invited' ? 'info' : 'warn')],
                    ['text' => $teacher->status === 'active' ? 'Active' : 'Inactive', 'badge' => $teacher->status === 'active' ? 'ok' : 'off'],
                ],
                'menu' => [
                    ['label' => 'View profile', 'href' => url('/admin/teacher/show/'.$teacher->name)],
                    ['label' => 'Edit', 'href' => url('/admin/teacher/edit/'.$teacher->name)],
                    ['label' => 'Send invite', 'href' => url('/admin/teacher/edit/'.$teacher->name)],
                    ['label' => 'Assign classes', 'href' => url('/admin/teacher/show/classes/'.$teacher->name)],
                ],
            ];
        }

        $counts = $this->teacherCounts($schoolId, $yearId);

        return $this->envelope('teachers', 'Teachers', $admin, $search, $chip, $paginator, $rows, $counts['all'], [
            'placeholder' => 'Search by name, email',
            'add_label' => 'Add teacher',
            'add_url' => url('/admin/teacher/add'),
            'import_url' => url('/admin/import'),
            'empty_copy' => 'Add your teaching staff, then send each one an invite to join.',
            'columns' => ['Teaches', 'Class teacher of', 'Invite', 'Status'],
            'bulk' => [
                ['label' => 'Send invites', 'href' => url('/admin/teachers')],
                ['label' => 'Export', 'href' => url('/admin/teacher/export')],
            ],
            'chips' => [
                $this->chip('all', 'All', $counts['all'], $chip, $request, '/admin/teachers'),
                $this->chip('active', 'Active', $counts['active'], $chip, $request, '/admin/teachers'),
                $this->chip('not_invited', 'Not yet invited', $counts['not_invited'], $chip, $request, '/admin/teachers'),
                $this->chip('pending', 'Invite pending', $counts['pending'], $chip, $request, '/admin/teachers'),
                $this->chip('class_teacher', 'Class teachers', $counts['class_teacher'], $chip, $request, '/admin/teachers'),
            ],
            'standard' => null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function parents(User $admin, Request $request): array
    {
        $schoolId = (int) $admin->school_id;
        $search = trim((string) $request->input('search', ''));
        $chip = $this->allowedChip($request, ['all', 'whatsapp', 'not_opted', 'never'], 'all');

        $query = User::query()
            ->with(['userprofile'])
            ->where('school_id', $schoolId)
            ->where('usergroup_id', 7)
            ->whereIn('status', ['active', 'inactive']);

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('mobile_no', 'like', '%'.$search.'%')
                    ->orWhereHas('userprofile', function (Builder $profile) use ($search) {
                        $profile->where('firstname', 'like', '%'.$search.'%')
                            ->orWhere('lastname', 'like', '%'.$search.'%');
                    });
            });
        }

        $this->applyParentChip($query, $chip, $schoolId);

        $paginator = $query->orderBy('name')->paginate(self::PER_PAGE)->withQueryString();
        $ids = collect($paginator->items())->pluck('id')->all();
        $children = $this->childrenByParent($schoolId, $ids);
        $whatsapp = $this->whatsappByUser($schoolId, $ids);
        $logins = $this->lastLogins($schoolId, $ids);

        $rows = [];
        foreach ($paginator as $parent) {
            $kids = $children[$parent->id] ?? [];
            $wa = $whatsapp[$parent->id] ?? 'Not opted in';
            $seen = $logins[$parent->id] ?? null;
            $rows[] = [
                'id' => $parent->id,
                'name' => $this->personName($parent),
                'profile_url' => url('/admin/parent/show/'.$parent->name),
                'user' => $parent,
                'card' => [
                    $kids !== [] ? implode(', ', $kids) : '—',
                    $wa,
                ],
                'cells' => [
                    ['text' => $kids !== [] ? implode(', ', $kids) : '—'],
                    ['text' => $parent->mobile_no ?: '—', 'class' => 'people-kls'],
                    ['text' => $wa, 'badge' => $wa === 'Opted in' ? 'ok' : ($wa === 'Asked' ? 'info' : 'off')],
                    [
                        'text' => $seen ? $seen->timezone(config('app.timezone'))->format('j M Y') : 'Never',
                        'last_login' => $seen ? 'seen' : 'never',
                    ],
                ],
                'menu' => [
                    ['label' => 'View profile', 'href' => url('/admin/parent/show/'.$parent->name)],
                    ['label' => 'Edit', 'href' => url('/admin/parent/edit/'.$parent->name)],
                    ['label' => 'Link a child', 'href' => url('/admin/parent/edit/'.$parent->name)],
                    ['label' => 'Send WhatsApp opt-in', 'href' => url('/admin/whatsapp/parents')],
                ],
            ];
        }

        $counts = $this->parentCounts($schoolId);

        return $this->envelope('parents', 'Parents', $admin, $search, $chip, $paginator, $rows, $counts['all'], [
            'placeholder' => 'Search by name, phone',
            'add_label' => 'Add parent',
            'add_url' => url('/admin/parent/add'),
            'import_url' => url('/admin/import'),
            'empty_copy' => 'Parents are added with their children, or you can add them here and link them.',
            'columns' => ['Children', 'Phone', 'WhatsApp', 'Last login'],
            'bulk' => [
                ['label' => 'Send WhatsApp opt-in', 'href' => url('/admin/whatsapp/parents')],
                ['label' => 'Export', 'href' => url('/admin/report/parents')],
            ],
            'chips' => [
                $this->chip('all', 'All', $counts['all'], $chip, $request, '/admin/parents'),
                $this->chip('whatsapp', 'On WhatsApp', $counts['whatsapp'], $chip, $request, '/admin/parents'),
                $this->chip('not_opted', 'Not opted in', $counts['not_opted'], $chip, $request, '/admin/parents'),
                $this->chip('never', 'Never logged in', $counts['never'], $chip, $request, '/admin/parents'),
            ],
            'standard' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function envelope(
        string $kind,
        string $title,
        User $admin,
        string $search,
        string $chip,
        LengthAwarePaginator $paginator,
        array $rows,
        int $schoolTotal,
        array $extra
    ): array {
        $filteredEmpty = $paginator->total() === 0;
        $narrowed = $search !== '' || ! in_array($chip, ['all', ''], true) || ! empty($extra['standard']);
        $noun = strtolower($title);

        return array_merge($extra, [
            'kind' => $kind,
            'title' => $title,
            'subtitle' => $schoolTotal.' '.$noun.' at '.$admin->school->name,
            'search' => $search,
            'chip' => $chip,
            'rows' => $rows,
            'paginator' => $paginator,
            'empty' => $filteredEmpty && ! $narrowed,
            'no_match' => $filteredEmpty && $narrowed,
            'url' => url('/admin/'.($kind === 'teachers' ? 'teachers' : ($kind === 'parents' ? 'parents' : 'students'))),
            'clear_url' => url('/admin/'.($kind === 'teachers' ? 'teachers' : ($kind === 'parents' ? 'parents' : 'students'))),
            'showing' => $paginator->total() === 0
                ? ''
                : 'Showing '.$paginator->firstItem().'–'.$paginator->lastItem().' of '.$paginator->total(),
        ]);
    }

    private function studentQuery(int $schoolId): Builder
    {
        $latestIds = DB::table('student_academics as sa')
            ->select('sa.user_id')
            ->selectRaw('max(sa.id) as id')
            ->where('sa.school_id', $schoolId)
            ->whereNull('sa.deleted_at')
            ->whereIn('sa.academic_year_id', function ($query) use ($schoolId) {
                $query->select('id')
                    ->from('academic_years')
                    ->where('school_id', $schoolId)
                    ->where('status', 1);
            })
            ->groupBy('sa.user_id');

        return User::query()
            ->where('users.school_id', $schoolId)
            ->where('users.usergroup_id', 6)
            ->whereIn('users.status', ['active', 'inactive'])
            ->leftJoinSub($latestIds, 'latest_ids', 'users.id', '=', 'latest_ids.user_id')
            ->leftJoin('student_academics as latest_sa', 'latest_sa.id', '=', 'latest_ids.id')
            ->leftJoin('standards_link', function ($join) use ($schoolId) {
                $join->on('latest_sa.standardLink_id', '=', 'standards_link.id')
                    ->where('standards_link.school_id', '=', $schoolId);
            })
            ->leftJoin('sections', 'standards_link.section_id', '=', 'sections.id')
            ->select(
                'users.*',
                'sections.name as class_name',
                'standards_link.stream as class_stream',
                'latest_sa.klassapp_student_id as kls_number',
                'latest_sa.standardLink_id as class_link_id'
            );
    }

    /**
     * @return array{all: int, active: int, inactive: int, noclass: int, noparent: int}
     */
    private function studentCounts(int $schoolId): array
    {
        $base = User::query()
            ->where('school_id', $schoolId)
            ->where('usergroup_id', 6)
            ->whereIn('status', ['active', 'inactive']);

        $withClass = $this->studentIdsWithClass($schoolId);
        $withParent = StudentParentLink::query()
            ->where('school_id', $schoolId)
            ->whereNotNull('parent_id')
            ->pluck('student_id');

        return [
            'all' => (clone $base)->count(),
            'active' => (clone $base)->where('status', 'active')->count(),
            'inactive' => (clone $base)->where('status', 'inactive')->count(),
            'noclass' => $this->countMissing($base, 'id', $withClass),
            'noparent' => $this->countMissing($base, 'id', $withParent),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, mixed>|array<int, mixed>  $present
     */
    private function countMissing(Builder $base, string $column, $present): int
    {
        $ids = collect($present)->filter()->values();
        if ($ids->isEmpty()) {
            return (clone $base)->count();
        }

        return (clone $base)->whereNotIn($column, $ids)->count();
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function studentIdsWithClass(int $schoolId)
    {
        $yearIds = AcademicYear::query()
            ->where('school_id', $schoolId)
            ->where('status', 1)
            ->pluck('id');

        return DB::table('student_academics')
            ->where('school_id', $schoolId)
            ->whereIn('academic_year_id', $yearIds)
            ->whereNotNull('standardLink_id')
            ->whereNull('deleted_at')
            ->pluck('user_id');
    }

    private function applyStudentSearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $query->where(function (Builder $builder) use ($search) {
            $builder->where('users.name', 'like', '%'.$search.'%')
                ->orWhere('latest_sa.klassapp_student_id', 'like', '%'.$search.'%')
                ->orWhereHas('userprofile', function (Builder $profile) use ($search) {
                    $profile->where('firstname', 'like', '%'.$search.'%')
                        ->orWhere('lastname', 'like', '%'.$search.'%');
                });
        });
    }

    private function applyStudentChip(Builder $query, string $chip, ?int $standardId, int $schoolId, Request $request): void
    {
        $status = $request->input('status');
        if (in_array($chip, ['active', 'inactive'], true)) {
            $query->where('users.status', $chip);
        } elseif (in_array($status, ['active', 'inactive'], true) && $chip !== 'all') {
            $query->where('users.status', $status);
        } elseif (in_array($status, ['active', 'inactive'], true) && $request->filled('standard')) {
            $query->where('users.status', $status);
        }

        if ($chip === 'noclass' || $request->input('standard') === 'none') {
            $query->whereNull('latest_sa.standardLink_id');
        } elseif ($chip === 'class' && $standardId) {
            $link = StandardLink::query()->where('school_id', $schoolId)->find($standardId);
            if ($link) {
                $query->where('standards_link.standard_id', $link->standard_id)
                    ->where('standards_link.section_id', $link->section_id);
            }
        } elseif ($standardId && $request->filled('standard') && $request->input('standard') !== 'none') {
            $link = StandardLink::query()->where('school_id', $schoolId)->find($standardId);
            if ($link) {
                $query->where('standards_link.standard_id', $link->standard_id)
                    ->where('standards_link.section_id', $link->section_id);
            }
        }

        if ($chip === 'noparent') {
            $query->whereNotIn('users.id', StudentParentLink::query()
                ->where('school_id', $schoolId)
                ->whereNotNull('parent_id')
                ->select('student_id'));
        }
    }

    private function studentChip(Request $request): string
    {
        $chip = (string) $request->input('chip', '');
        if (in_array($chip, ['all', 'class', 'active', 'inactive', 'noclass', 'noparent'], true)) {
            return $chip;
        }
        if ($request->input('standard') === 'none') {
            return 'noclass';
        }
        if ($request->filled('standard')) {
            return 'class';
        }
        if ($request->input('status') === 'inactive') {
            return 'inactive';
        }
        if ($request->input('status') === 'active') {
            return 'active';
        }

        return 'all';
    }

    /**
     * @param  list<string>  $allowed
     */
    private function allowedChip(Request $request, array $allowed, string $default): string
    {
        $chip = (string) $request->input('chip', '');
        if (in_array($chip, $allowed, true)) {
            return $chip;
        }
        if ($request->input('status') === 'active' && in_array('active', $allowed, true)) {
            return 'active';
        }
        if ($request->input('status') === 'inactive' && in_array('inactive', $allowed, true)) {
            return 'inactive';
        }

        return $default;
    }

    private function applyTeacherChip(Builder $query, string $chip, int $schoolId, ?int $yearId): void
    {
        if ($chip === 'active') {
            $query->where('status', 'active');
        }
        if ($chip === 'not_invited') {
            $pending = $this->pendingInviteEmailQuery($schoolId);
            $query->where(function (Builder $builder) {
                $builder->whereNull('email_verified')->orWhere('email_verified', 0);
            })->whereNotIn(DB::raw('lower(email)'), $pending);
        }
        if ($chip === 'pending') {
            $query->where(function (Builder $builder) {
                $builder->whereNull('email_verified')->orWhere('email_verified', 0);
            })->whereIn(DB::raw('lower(email)'), $this->pendingInviteEmailQuery($schoolId));
        }
        if ($chip === 'class_teacher') {
            $ids = $yearId
                ? StandardLink::query()
                    ->where('school_id', $schoolId)
                    ->where('academic_year_id', $yearId)
                    ->whereNotNull('class_teacher_id')
                    ->pluck('class_teacher_id')
                : collect();
            if ($ids->isEmpty()) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('id', $ids);
            }
        }
    }

    /**
     * @return array{all: int, active: int, not_invited: int, pending: int, class_teacher: int}
     */
    private function teacherCounts(int $schoolId, ?int $yearId): array
    {
        $base = User::query()->where('school_id', $schoolId)->where('usergroup_id', 5)->whereIn('status', ['active', 'inactive']);
        $pending = $this->pendingInviteEmailQuery($schoolId);

        return [
            'all' => (clone $base)->count(),
            'active' => (clone $base)->where('status', 'active')->count(),
            'not_invited' => (clone $base)->where(function (Builder $builder) {
                $builder->whereNull('email_verified')->orWhere('email_verified', 0);
            })->whereNotIn(DB::raw('lower(email)'), $pending)->count(),
            'pending' => (clone $base)->where(function (Builder $builder) {
                $builder->whereNull('email_verified')->orWhere('email_verified', 0);
            })->whereIn(DB::raw('lower(email)'), clone $pending)->count(),
            'class_teacher' => $yearId
                ? (clone $base)->whereIn('id', StandardLink::query()
                    ->where('school_id', $schoolId)
                    ->where('academic_year_id', $yearId)
                    ->whereNotNull('class_teacher_id')
                    ->select('class_teacher_id'))->count()
                : 0,
        ];
    }

    private function applyParentChip(Builder $query, string $chip, int $schoolId): void
    {
        if ($chip === 'whatsapp') {
            $query->whereIn('id', WhatsAppUser::query()
                ->where('school_id', $schoolId)
                ->where('opted_in', true)
                ->select('user_id'));
        }
        if ($chip === 'not_opted') {
            $query->whereNotIn('id', WhatsAppUser::query()
                ->where('school_id', $schoolId)
                ->where('opted_in', true)
                ->select('user_id'));
        }
        if ($chip === 'never') {
            $query->whereNotIn('id', ActivityLog::query()
                ->where('school_id', $schoolId)
                ->where('log_name', 'login')
                ->select('causer_id'));
        }
    }

    /**
     * @return array{all: int, whatsapp: int, not_opted: int, never: int}
     */
    private function parentCounts(int $schoolId): array
    {
        $base = User::query()->where('school_id', $schoolId)->where('usergroup_id', 7)->whereIn('status', ['active', 'inactive']);
        $opted = WhatsAppUser::query()->where('school_id', $schoolId)->where('opted_in', true)->select('user_id');

        return [
            'all' => (clone $base)->count(),
            'whatsapp' => (clone $base)->whereIn('id', $opted)->count(),
            'not_opted' => (clone $base)->whereNotIn('id', clone $opted)->count(),
            'never' => (clone $base)->whereNotIn('id', ActivityLog::query()
                ->where('school_id', $schoolId)
                ->where('log_name', 'login')
                ->select('causer_id'))->count(),
        ];
    }

    private function pendingInviteEmailQuery(int $schoolId)
    {
        return TeacherInvite::query()
            ->where('school_id', $schoolId)
            ->whereNull('claimed_at')
            ->where('expires_at', '>', now())
            ->selectRaw('lower(email)');
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, list<string>>
     */
    private function subjectsByTeacher(int $schoolId, ?int $yearId, array $ids): array
    {
        if ($ids === [] || ! $yearId) {
            return [];
        }

        $links = Teacherlink::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $yearId)
            ->whereIn('teacher_id', $ids)
            ->with('subject')
            ->get();

        $grouped = [];
        foreach ($links as $link) {
            $name = $link->subject->name ?? null;
            if ($name) {
                $label = $this->titleLabel($name);
                $grouped[$link->teacher_id][$label] = $label;
            }
        }

        return array_map(fn ($names) => array_values($names), $grouped);
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, list<string>>
     */
    private function classTeacherLabels(int $schoolId, ?int $yearId, array $ids): array
    {
        if ($ids === [] || ! $yearId) {
            return [];
        }

        $links = StandardLink::query()
            ->with('section')
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $yearId)
            ->whereIn('class_teacher_id', $ids)
            ->get();

        $grouped = [];
        foreach ($links as $link) {
            $label = $this->classLabel($link->section->name ?? null, $link->stream);
            if ($label) {
                $grouped[$link->class_teacher_id][] = $label;
            }
        }

        return $grouped;
    }

    /**
     * @param  list<string>  $emails
     * @return array<string, true>
     */
    private function pendingInviteEmails(int $schoolId, array $emails): array
    {
        if ($emails === []) {
            return [];
        }

        return TeacherInvite::query()
            ->where('school_id', $schoolId)
            ->whereNull('claimed_at')
            ->where('expires_at', '>', now())
            ->get()
            ->filter(fn (TeacherInvite $invite) => in_array(strtolower($invite->email), $emails, true))
            ->mapWithKeys(fn (TeacherInvite $invite) => [strtolower($invite->email) => true])
            ->all();
    }

    private function inviteLabel(User $teacher, bool $pending): string
    {
        if ((int) $teacher->email_verified === 1) {
            return 'Joined';
        }
        if ($pending) {
            return 'Invited';
        }

        return 'Not invited';
    }

    private function teacherRole(User $teacher): string
    {
        $profiles = $teacher->teacherprofile;
        $profile = $profiles instanceof \Illuminate\Support\Collection ? $profiles->first() : $profiles;
        $designation = $profile->designation ?? null;
        if (is_string($designation) && trim($designation) !== '') {
            return str_replace('_', ' ', ucwords($designation));
        }

        return 'Teacher';
    }

    private function personName(User $user): string
    {
        $profile = $user->userprofile;
        $first = trim((string) ($profile->firstname ?? ''));
        $last = trim((string) ($profile->lastname ?? ''));
        $name = trim($first.' '.$last);

        return $this->titleLabel($name !== '' ? $name : (string) $user->name);
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, list<string>>
     */
    private function childrenByParent(int $schoolId, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $links = StudentParentLink::query()
            ->with('userStudent.userprofile')
            ->where('school_id', $schoolId)
            ->whereIn('parent_id', $ids)
            ->get();

        $grouped = [];
        foreach ($links as $link) {
            $student = $link->userStudent;
            if ($student && (int) $student->school_id === $schoolId) {
                $grouped[$link->parent_id][] = $this->personName($student);
            }
        }

        return $grouped;
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, string>
     */
    private function whatsappByUser(int $schoolId, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $rows = WhatsAppUser::query()
            ->where('school_id', $schoolId)
            ->whereIn('user_id', $ids)
            ->get();

        $labels = [];
        foreach ($rows as $row) {
            if ($row->opted_in) {
                $labels[$row->user_id] = 'Opted in';
            } elseif ($row->verified_at === null) {
                $labels[$row->user_id] = 'Asked';
            } else {
                $labels[$row->user_id] = 'Not opted in';
            }
        }

        return $labels;
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, \Illuminate\Support\Carbon>
     */
    private function lastLogins(int $schoolId, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $logs = ActivityLog::query()
            ->where('school_id', $schoolId)
            ->where('log_name', 'login')
            ->whereIn('causer_id', $ids)
            ->orderByDesc('id')
            ->get();

        $seen = [];
        foreach ($logs as $log) {
            if (! isset($seen[$log->causer_id])) {
                $seen[$log->causer_id] = $log->created_at;
            }
        }

        return $seen;
    }

    /**
     * @return array{name: string, slug: string}|null
     */
    private function firstParent(User $student, int $schoolId): ?array
    {
        foreach ($student->parents as $link) {
            if ((int) $link->school_id !== $schoolId) {
                continue;
            }
            $parent = $link->userParent;
            if ($parent && (int) $parent->school_id === $schoolId) {
                return [
                    'name' => $this->personName($parent),
                    'slug' => $parent->name,
                ];
            }
        }

        return null;
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    private function classOptions(int $schoolId): array
    {
        $yearId = $this->currentYearId($schoolId);
        if (! $yearId) {
            return [];
        }

        return StandardLink::query()
            ->with(['section', 'standard'])
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $yearId)
            ->orderBy('id')
            ->get()
            ->map(fn (StandardLink $link) => [
                'id' => $link->id,
                'label' => $this->classLabel($link->section->name ?? null, $link->stream) ?? 'Class',
            ])
            ->all();
    }

    private function classOptionLabel(int $schoolId, int $standardId): ?string
    {
        $link = StandardLink::query()->with('section')->where('school_id', $schoolId)->find($standardId);
        if (! $link) {
            return null;
        }

        return $this->classLabel($link->section->name ?? null, $link->stream);
    }

    private function classLabel(?string $section, ?string $stream): ?string
    {
        $section = trim((string) $section);
        $stream = trim((string) $stream);
        if ($section === '' && $stream === '') {
            return null;
        }
        if ($stream === '') {
            return $this->titleLabel($section);
        }
        if ($section === '') {
            return $this->titleLabel($stream);
        }

        return $this->titleLabel($section).' · '.$this->titleLabel($stream);
    }

    private function titleLabel(string $value): string
    {
        return mb_convert_case(mb_strtolower($value), MB_CASE_TITLE, 'UTF-8');
    }

    private function currentYearId(int $schoolId): ?int
    {
        $id = AcademicYear::query()
            ->where('school_id', $schoolId)
            ->where('status', 1)
            ->orderByDesc('id')
            ->value('id');

        return $id ? (int) $id : null;
    }

    private function sameSchoolStandardId(int $schoolId, mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === 'none' || ! ctype_digit((string) $value)) {
            return null;
        }

        $id = (int) $value;
        $exists = StandardLink::query()->where('school_id', $schoolId)->whereKey($id)->exists();

        return $exists ? $id : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function chip(string $key, string $label, int $count, string $current, Request $request, string $path): array
    {
        $params = [];
        if ($key !== 'all') {
            $params['chip'] = $key;
        }
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $params['search'] = $search;
        }

        return [
            'key' => $key,
            'label' => $label,
            'count' => $count,
            'pressed' => $current === $key,
            'href' => url($path).($params === [] ? '' : '?'.http_build_query($params)),
        ];
    }

    /**
     * @param  list<array{id: int, label: string}>  $options
     * @return array<string, mixed>
     */
    private function classChip(string $current, ?string $selected, array $options, string $search, string $path): array
    {
        $links = [];
        foreach ($options as $option) {
            $params = ['chip' => 'class', 'standard' => $option['id']];
            if ($search !== '') {
                $params['search'] = $search;
            }
            $links[] = [
                'label' => $option['label'],
                'href' => url($path).'?'.http_build_query($params),
            ];
        }

        return [
            'key' => 'class',
            'label' => $selected ? 'Class: '.$selected : 'Class',
            'count' => null,
            'pressed' => $current === 'class',
            'picker' => true,
            'options' => $links,
        ];
    }
}
