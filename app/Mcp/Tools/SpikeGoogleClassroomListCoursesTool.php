<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

/**
 * Mock Google Classroom courses.list — returns synthetic courses matching
 * the real API response shape from developers.google.com.
 *
 * Course resource fields (real shape):
 *   id, name, section, descriptionHeading, description, room, ownerId,
 *   creationTime, updateTime, enrollmentCode, courseState, alternateLink,
 *   teacherGroupEmail, courseGroupEmail, teacherFolder (DriveFolder),
 *   courseMaterialSets[] (deprecated), guardiansEnabled, calendarId,
 *   gradebookSettings, subject, learningStandardSettings, levels
 *
 * Mock returns a minimal projection of the fields the wave-1 tool projects.
 */
#[Name('google_classroom_list_courses')]
#[Description('Mock Google Classroom courses.list — returns fixture courses (read-only).')]
class SpikeGoogleClassroomListCoursesTool extends Tool
{
    public function handle(Request $request): ResponseFactory
    {
        $pageSize = max(1, min((int) ($request->get('pageSize') ?? 2), 5));
        $pageToken = $request->get('pageToken');

        // Synthetic courses matching the real Course JSON shape.
        $allCourses = [
            [
                'id' => '999001',
                'name' => '10th Grade Biology',
                'section' => 'Period 1',
                'descriptionHeading' => 'Welcome to Biology',
                'courseState' => 'ACTIVE',
                'creationTime' => '2026-01-15T08:00:00Z',
                'updateTime' => '2026-09-20T10:30:00Z',
            ],
            [
                'id' => '999002',
                'name' => '11th Grade Chemistry',
                'section' => 'Period 3',
                'descriptionHeading' => 'Organic Chemistry',
                'courseState' => 'ACTIVE',
                'creationTime' => '2026-02-01T09:00:00Z',
                'updateTime' => '2026-09-19T14:15:00Z',
            ],
            [
                'id' => '999003',
                'name' => '12th Grade Physics',
                'section' => 'Period 2',
                'descriptionHeading' => 'Electromagnetism',
                'courseState' => 'ARCHIVED',
                'creationTime' => '2025-08-15T07:30:00Z',
                'updateTime' => '2026-06-01T12:00:00Z',
            ],
        ];

        // Paginate: page 1 returns first N, page 2 returns rest.
        if ($pageToken === 'page2') {
            $courses = array_slice($allCourses, $pageSize);
            $nextPageToken = null;
        } else {
            $courses = array_slice($allCourses, 0, $pageSize);
            $nextPageToken = count($allCourses) > $pageSize ? 'page2' : null;
        }

        return Response::structured([
            'ok' => true,
            'courses' => $courses,
            'nextPageToken' => $nextPageToken,
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'pageSize' => $schema->integer()
                ->description('Max courses to return (1–100).')
                ->default(20),
            'pageToken' => $schema->string()
                ->description('Pagination token from a previous response, if any.')
                ->nullable(),
        ];
    }
}
