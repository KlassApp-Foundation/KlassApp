<?php

namespace App\Mcp\Tools;

use App\Models\SchoolMcpConnector;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

/**
 * Read-only Google Classroom courses.courseWork.list — coursework +
 * due dates for one of the connecting teacher's courses.
 *
 * Real REST call to classroom.googleapis.com (production tool surface).
 *
 * Wave-1 scope: classroom.coursework.students.readonly only. Returns
 * coursework metadata (title, description, dueDate/dueTime, state, link)
 * — NOT student submissions or grades (those need separate scopes and
 * are explicitly excluded from wave-1 per the approved plan).
 */
#[Name('google_classroom_list_coursework')]
#[Description('List coursework and due dates for one Google Classroom course (read-only). Requires a course id from google_classroom_list_courses.')]
class GoogleClassroomListCourseworkTool extends Tool
{
    public function handle(Request $request): ResponseFactory
    {
        $token = SchoolMcpConnector::resolveTokenForRequest('google-classroom');

        if ($token === null) {
            return Response::structured([
                'ok' => false,
                'error' => 'not_connected',
                'message' => 'Google Classroom is not connected for this school. '
                    .'Ask the admin to connect it in School Settings → Integrations.',
            ]);
        }

        $courseId = (string) $request->get('courseId', '');

        if ($courseId === '') {
            return Response::structured([
                'ok' => false,
                'error' => 'missing_course_id',
                'message' => 'A courseId is required. Get course ids from google_classroom_list_courses.',
            ]);
        }

        $pageSize = max(1, min((int) ($request->get('pageSize') ?? 20), 100));
        $pageToken = $request->get('pageToken');

        $response = Http::withToken($token)
            ->acceptJson()
            ->get("https://classroom.googleapis.com/v1/courses/{$courseId}/courseWork", array_filter([
                'pageSize' => $pageSize,
                'pageToken' => $pageToken,
            ]));

        if ($response->failed()) {
            $status = $response->status();

            return Response::structured([
                'ok' => false,
                'error' => $status === 404
                    ? 'course_not_found'
                    : 'google_classroom_api_error',
                'status' => $status,
                'message' => $status === 404
                    ? 'Course not found (check the course id).'
                    : 'Google Classroom API request failed.',
            ]);
        }

        $data = $response->json();

        // Coursework metadata only — no submissions, no grades.
        $courseWork = collect($data['courseWork'] ?? [])->map(fn ($work) => [
            'id' => $work['id'] ?? null,
            'title' => $work['title'] ?? null,
            'description' => $work['description'] ?? null,
            'state' => $work['state'] ?? null,
            'workType' => $work['workType'] ?? null,
            'dueDate' => $work['dueDate'] ?? null,
            'dueTime' => $work['dueTime'] ?? null,
            'alternateLink' => $work['alternateLink'] ?? null,
            'creationTime' => $work['creationTime'] ?? null,
            'updateTime' => $work['updateTime'] ?? null,
        ])->all();

        return Response::structured([
            'ok' => true,
            'courseId' => $courseId,
            'courseWork' => $courseWork,
            'nextPageToken' => $data['nextPageToken'] ?? null,
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
