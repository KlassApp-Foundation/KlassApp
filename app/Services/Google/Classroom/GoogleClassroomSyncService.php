<?php

namespace App\Services\Google\Classroom;

use App\Models\AcademicYear;
use App\Models\SchoolGoogleConnection;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Services\Google\GoogleApiClient;
use App\Services\Google\GoogleApiException;
use App\Services\Google\GoogleConnectorCatalog;
use Illuminate\Support\Collection;

class GoogleClassroomSyncService
{
    public function __construct(
        private readonly GoogleApiClient $client,
        private readonly GoogleConnectorCatalog $catalog,
    ) {
    }

    /**
     * Read Classroom courses and rosters. Match a course only when one active
     * section in this school has that exact name. Match a student only when
     * one active student in that section has that exact name. Never creates users.
     *
     * @return array{
     *     classes: list<array<string, mixed>>,
     *     needsReview: list<array<string, mixed>>
     * }
     */
    public function sync(User $actor): array
    {
        $schoolId = (int) $actor->school_id;
        $connection = $this->connection($actor);
        $sections = Section::query()
            ->where('school_id', $schoolId)
            ->where('status', 1)
            ->get()
            ->groupBy(fn (Section $section) => trim((string) $section->name));

        $classes = [];
        $needsReview = [];

        foreach ($this->courses($connection) as $course) {
            if (($course['courseState'] ?? '') !== 'ACTIVE') {
                continue;
            }

            $courseId = (string) ($course['id'] ?? '');
            $name = trim((string) ($course['name'] ?? ''));
            $hits = $name === '' ? collect() : $sections->get($name, collect());

            if ($courseId === '' || preg_match('/^[A-Za-z0-9_-]+$/', $courseId) !== 1 || $hits->count() !== 1) {
                $needsReview[] = [
                    'courseId' => $courseId,
                    'name' => $name,
                    'reason' => $hits->count() > 1 ? 'ambiguous_section' : 'no_section',
                ];

                continue;
            }

            /** @var Section $section */
            $section = $hits->first();
            $roster = $this->matchStudents($schoolId, $section, $this->students($connection, $courseId));
            $classes[] = [
                'courseId' => $courseId,
                'sectionId' => $section->id,
                'name' => $name,
                'matched' => $roster['matched'],
                'needsReview' => $roster['needsReview'],
            ];
        }

        return ['classes' => $classes, 'needsReview' => $needsReview];
    }

    private function connection(User $actor): SchoolGoogleConnection
    {
        $connection = SchoolGoogleConnection::query()->forSchool((int) $actor->school_id)->first();

        if (! $this->catalog->isLive($connection, 'classroom')) {
            throw new GoogleApiException(404, 'Google Classroom is not connected for this school.', 'not_connected');
        }

        return $connection;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function courses(SchoolGoogleConnection $connection): array
    {
        $courses = [];
        $pageToken = null;

        for ($page = 0; $page < 5; $page++) {
            $query = ['pageSize' => 100, 'courseStates' => 'ACTIVE'];
            if (is_string($pageToken) && $pageToken !== '') {
                $query['pageToken'] = $pageToken;
            }
            $data = $this->client->get($connection, ClassroomEndpoint::url('/v1/courses'), $query);
            foreach ($data['courses'] ?? [] as $course) {
                if (is_array($course)) {
                    $courses[] = $course;
                }
            }
            $pageToken = $data['nextPageToken'] ?? null;
            if (! is_string($pageToken) || $pageToken === '') {
                break;
            }
        }

        return $courses;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function students(SchoolGoogleConnection $connection, string $courseId): array
    {
        $students = [];
        $pageToken = null;

        for ($page = 0; $page < 5; $page++) {
            $query = ['pageSize' => 100];
            if (is_string($pageToken) && $pageToken !== '') {
                $query['pageToken'] = $pageToken;
            }
            $data = $this->client->get($connection, ClassroomEndpoint::url('/v1/courses/'.$courseId.'/students'), $query);
            foreach ($data['students'] ?? [] as $student) {
                if (is_array($student)) {
                    $students[] = $student;
                }
            }
            $pageToken = $data['nextPageToken'] ?? null;
            if (! is_string($pageToken) || $pageToken === '') {
                break;
            }
        }

        return $students;
    }

    /**
     * @param  list<array<string, mixed>>  $remote
     * @return array{matched: list<array<string, mixed>>, needsReview: list<array<string, mixed>>}
     */
    private function matchStudents(int $schoolId, Section $section, array $remote): array
    {
        $yearId = AcademicYear::query()
            ->where('school_id', $schoolId)
            ->where('status', 1)
            ->orderByDesc('id')
            ->value('id');

        $linkIds = StandardLink::query()
            ->where('school_id', $schoolId)
            ->where('section_id', $section->id)
            ->when($yearId !== null, fn ($query) => $query->where('academic_year_id', $yearId))
            ->pluck('id');

        $userIds = StudentAcademic::query()
            ->where('school_id', $schoolId)
            ->whereIn('standardLink_id', $linkIds)
            ->pluck('user_id');

        /** @var Collection<string, Collection<int, User>> $grouped */
        $grouped = User::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $userIds)
            ->where('usergroup_id', 6)
            ->where('status', 'active')
            ->get()
            ->groupBy(fn (User $user) => trim((string) $user->name));

        $matched = [];
        $needsReview = [];

        foreach ($remote as $student) {
            $fullName = trim((string) ($student['profile']['name']['fullName'] ?? ''));
            $googleUserId = (string) ($student['userId'] ?? '');
            $hits = $fullName === '' ? collect() : $grouped->get($fullName, collect());

            if ($hits->count() === 1) {
                $matched[] = [
                    'googleUserId' => $googleUserId,
                    'userId' => $hits->first()->id,
                    'name' => $fullName,
                ];

                continue;
            }

            $needsReview[] = [
                'googleUserId' => $googleUserId,
                'name' => $fullName,
                'reason' => $hits->count() > 1 ? 'ambiguous_student' : 'no_student',
            ];
        }

        return ['matched' => $matched, 'needsReview' => $needsReview];
    }
}
