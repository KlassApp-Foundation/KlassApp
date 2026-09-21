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
 * Mock Google Classroom courses.courseWork.list — returns synthetic coursework
 * matching the real API response shape from developers.google.com.
 *
 * CourseWork resource fields (real shape):
 *   courseId, id, title, description, materials[], state, alternateLink,
 *   creationTime, updateTime, dueDate (Date), dueTime (TimeOfDay),
 *   scheduledTime, maxPoints, workType, associatedWithDeveloper,
 *   assigneeMode, individualStudentsOptions, submissionModificationMode,
 *   creatorUserId, topicId, gradeCategory, previewVersion, learningGoals[],
 *   assignment (Assignment) / multipleChoiceQuestion (MultipleChoiceQuestion)
 *
 * Mock returns a minimal projection of the fields the wave-1 tool projects.
 */
#[Name('google_classroom_list_coursework')]
#[Description('Mock Google Classroom courses.courseWork.list — returns fixture coursework with due dates (read-only).')]
class SpikeGoogleClassroomListCourseworkTool extends Tool
{
    public function handle(Request $request): ResponseFactory
    {
        $courseId = (string) $request->get('courseId', '');

        if ($courseId === '') {
            return Response::structured([
                'ok' => false,
                'error' => 'missing_course_id',
                'message' => 'A courseId is required. Get course ids from google_classroom_list_courses.',
            ]);
        }

        $pageSize = max(1, min((int) ($request->get('pageSize') ?? 2), 5));
        $pageToken = $request->get('pageToken');

        // Synthetic coursework matching the real CourseWork JSON shape.
        // dueDate shape: {year, month, day}; dueTime shape: {hours, minutes, seconds, nanos}.
        $allCoursework = match ($courseId) {
            '999001' => [
                [
                    'id' => 'cw-001',
                    'title' => 'Cell Structure Diagram',
                    'description' => 'Draw and label a diagram of a plant cell.',
                    'state' => 'PUBLISHED',
                    'workType' => 'ASSIGNMENT',
                    'dueDate' => ['year' => 2026, 'month' => 9, 'day' => 25],
                    'dueTime' => ['hours' => 17, 'minutes' => 0, 'seconds' => 0, 'nanos' => 0],
                    'alternateLink' => 'https://classroom.google.com/c/OTk5MDAx/a/cw-001/details',
                    'creationTime' => '2026-09-15T10:00:00Z',
                    'updateTime' => '2026-09-15T10:00:00Z',
                ],
                [
                    'id' => 'cw-002',
                    'title' => 'Read Chapter 3',
                    'description' => 'Read pages 45-62 and answer review questions.',
                    'state' => 'PUBLISHED',
                    'workType' => 'ASSIGNMENT',
                    'dueDate' => ['year' => 2026, 'month' => 9, 'day' => 22],
                    'dueTime' => null,
                    'alternateLink' => 'https://classroom.google.com/c/OTk5MDAx/a/cw-002/details',
                    'creationTime' => '2026-09-10T08:00:00Z',
                    'updateTime' => '2026-09-10T08:00:00Z',
                ],
            ],
            '999002' => [
                [
                    'id' => 'cw-003',
                    'title' => 'Organic Nomenclature Quiz',
                    'description' => 'Online quiz on IUPAC naming conventions.',
                    'state' => 'PUBLISHED',
                    'workType' => 'MULTIPLE_CHOICE_QUESTION',
                    'dueDate' => ['year' => 2026, 'month' => 9, 'day' => 28],
                    'dueTime' => ['hours' => 9, 'minutes' => 0, 'seconds' => 0, 'nanos' => 0],
                    'alternateLink' => 'https://classroom.google.com/c/OTk5MDAy/a/cw-003/details',
                    'creationTime' => '2026-09-18T12:00:00Z',
                    'updateTime' => '2026-09-18T12:00:00Z',
                ],
            ],
            default => [
                [
                    'id' => 'cw-999',
                    'title' => 'General Assignment',
                    'description' => 'Coursework for course '.$courseId,
                    'state' => 'PUBLISHED',
                    'workType' => 'ASSIGNMENT',
                    'dueDate' => ['year' => 2026, 'month' => 12, 'day' => 31],
                    'dueTime' => null,
                    'alternateLink' => 'https://classroom.google.com/c/'.urlencode($courseId).'/a/default/details',
                    'creationTime' => '2026-01-01T00:00:00Z',
                    'updateTime' => '2026-01-01T00:00:00Z',
                ],
            ],
        };

        if ($pageToken === 'page2') {
            $coursework = array_slice($allCoursework, $pageSize);
            $nextPageToken = null;
        } else {
            $coursework = array_slice($allCoursework, 0, $pageSize);
            $nextPageToken = count($allCoursework) > $pageSize ? 'page2' : null;
        }

        return Response::structured([
            'ok' => true,
            'courseId' => $courseId,
            'courseWork' => $coursework,
            'nextPageToken' => $nextPageToken,
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'courseId' => $schema->string()
                ->description('Course id from google_classroom_list_courses.'),
            'pageSize' => $schema->integer()
                ->description('Max coursework items to return (1–100).')
                ->default(20),
            'pageToken' => $schema->string()
                ->description('Pagination token from a previous response, if any.')
                ->nullable(),
        ];
    }
}
