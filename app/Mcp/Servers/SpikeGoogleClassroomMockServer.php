<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\SpikeGoogleClassroomListCoursesTool;
use App\Mcp\Tools\SpikeGoogleClassroomListCourseworkTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

/**
 * Local fixture MCP server standing in for the Google Classroom REST API
 * during tests. Registered as handle `google-classroom-mock`.
 *
 * Returns synthetic response data matching the real Google Classroom API
 * response shapes (verified against developers.google.com docs).
 */
#[Name('Spike Google Classroom Mock')]
#[Version('0.0.1')]
#[Instructions('Throwaway mock Google Classroom MCP for KlassApp Mcp::client(\'google-classroom\') tests. Not real Classroom.')]
class SpikeGoogleClassroomMockServer extends Server
{
    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        SpikeGoogleClassroomListCoursesTool::class,
        SpikeGoogleClassroomListCourseworkTool::class,
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
