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
 * Read-only Google Classroom courses.list — the connecting teacher's courses.
 *
 * Real REST call to classroom.googleapis.com (NOT a mock — unlike
 * SpikeSlack*Tool, this is production Classroom tool surface).
 *
 * Wave-1 scope: classroom.courses.readonly only. No roster/student data
 * is requested or returned by this tool (course id, name, section, state,
 * description heading, timestamps only).
 *
 * Token resolution reuses the registry (SchoolMcpConnector) so the call
 * is per-school and audited at the client layer (AuditingMcpClient wrap
 * applies to the named 'google-classroom' client that invokes this server).
 */
#[Name('google_classroom_list_courses')]
#[Description('List the connecting teacher\'s Google Classroom courses (read-only).')]
class GoogleClassroomListCoursesTool extends Tool
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

        $pageSize = max(1, min((int) ($request->get('pageSize') ?? 20), 100));
        $pageToken = $request->get('pageToken');

        $response = Http::withToken($token)
            ->acceptJson()
            ->get('https://classroom.googleapis.com/v1/courses', array_filter([
                'pageSize' => $pageSize,
                'pageToken' => $pageToken,
                // courseStates: ACTIVE + ARCHIVED so teachers can see past
                // courses too; PROVISIONED omitted (never fully created).
                'courseStates' => 'ACTIVE,ARCHIVED',
            ]));

        if ($response->failed()) {
            return Response::structured([
                'ok' => false,
                'error' => 'google_classroom_api_error',
                'status' => $response->status(),
                'message' => 'Google Classroom API request failed.',
            ]);
        }

        $data = $response->json();

        // Deliberately minimal field projection — no student/teacher PII.
        $courses = collect($data['courses'] ?? [])->map(fn ($course) => [
            'id' => $course['id'] ?? null,
            'name' => $course['name'] ?? null,
            'section' => $course['section'] ?? null,
            'descriptionHeading' => $course['descriptionHeading'] ?? null,
            'courseState' => $course['courseState'] ?? null,
            'creationTime' => $course['creationTime'] ?? null,
            'updateTime' => $course['updateTime'] ?? null,
        ])->all();

        return Response::structured([
            'ok' => true,
            'courses' => $courses,
            'nextPageToken' => $data['nextPageToken'] ?? null,
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'pageSize' => $schema->integer()
                ->description('Max courses to return (1–100, Google page cap).')
                ->default(20),
            'pageToken' => $schema->string()
                ->description('Pagination token from a previous response, if any.')
                ->nullable(),
        ];
    }
}
