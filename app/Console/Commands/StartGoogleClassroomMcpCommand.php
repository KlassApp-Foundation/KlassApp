<?php

namespace App\Console\Commands;

use App\Models\School;
use Illuminate\Console\Command;
use Laravel\Mcp\Server\Registrar;

/**
 * Starts the Google Classroom local MCP server with an explicit school context.
 *
 * The local MCP client is a stdio subprocess, so it has no HTTP auth session.
 * The web process passes the current school id as an argument; this command
 * stores it in config before the server starts, and the tool handlers resolve
 * the registry token from that context.
 */
class StartGoogleClassroomMcpCommand extends Command
{
    protected $signature = 'google-classroom:mcp
        {school : KlassApp school id}';

    protected $description = 'Start the Google Classroom MCP server for one school';

    public function handle(Registrar $registrar): int
    {
        $schoolId = (int) $this->argument('school');

        if ($schoolId <= 0 || ! School::whereKey($schoolId)->exists()) {
            $this->error('A valid school id is required.');

            return self::FAILURE;
        }

        config(['toshi.mcp_context.school_id' => $schoolId]);

        $server = $registrar->getLocalServer('google-classroom');

        if ($server === null) {
            $this->error('Google Classroom MCP server is not registered.');

            return self::FAILURE;
        }

        $server();

        return self::SUCCESS;
    }
}
