<?php

namespace App\Mcp\Tools;

use App\Models\SchoolMcpConnector;
use App\Services\Google\Classroom\ClassroomEndpoint;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

/**
 * Read-only course roster. Returns the Classroom user id and full name.
 * Email, guardian, and profile-email fields are dropped even if Google sends them.
 */
#[Name('google_classroom_list_students')]
#[Description('List student names for one Google Classroom course (read-only). Requires a course id from google_classroom_list_courses.')]
class GoogleClassroomListStudentsTool extends Tool
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

        if ($courseId === '' || preg_match('/^[A-Za-z0-9_-]+$/', $courseId) !== 1) {
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
            ->get(ClassroomEndpoint::url('/v1/courses/'.$courseId.'/students'), array_filter([
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
        $students = collect($data['students'] ?? [])->map(fn ($student) => [
            'userId' => $student['userId'] ?? null,
            'fullName' => $student['profile']['name']['fullName'] ?? null,
        ])->all();

        return Response::structured([
            'ok' => true,
            'courseId' => $courseId,
            'students' => $students,
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
                ->description('Max students to return (1–100).')
                ->default(20),
            'pageToken' => $schema->string()
                ->description('Pagination token from a previous response, if any.')
                ->nullable(),
        ];
    }
}
