<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\GoogleClassroomListCoursesTool;
use App\Mcp\Tools\GoogleClassroomListCourseworkTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

/**
 * Self-hosted LOCAL MCP server wrapping the Google Classroom REST API
 * (Thread A, option b — docs/plans/toshi-direct-google-integrations-
 * and-custom-connector-registry-plan.md A.3).
 *
 * Unlike SpikeSlackMockServer (fixture data), the tools here call the real
 * classroom.googleapis.com REST endpoints with per-school tokens resolved
 * from the school_mcp_connectors registry.
 *
 * Registered via Mcp::local('google-classroom', …) in routes/ai.php —
 * the in-process stdio transport means both ends speak the laravel/mcp
 * 0.8.2 era; the 2026-07-28 remote-server transport gap does not apply.
 *
 * Wave-1: READ-ONLY. Two tools, two sensitive scopes
 * (classroom.courses.readonly + classroom.coursework.students.readonly).
 * No roster/email/guardian scopes, no write tools.
 */
#[Name('Google Classroom')]
#[Version('0.1.0')]
#[Instructions('KlassApp school connector for Google Classroom (read-only wave-1): list the connecting teacher\'s courses and coursework due dates. No roster, email, guardian, submission, or grade data.')]
class GoogleClassroomServer extends Server
{
    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        GoogleClassroomListCoursesTool::class,
        GoogleClassroomListCourseworkTool::class,
    ];

    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected array $resources = [];

    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [];
}
