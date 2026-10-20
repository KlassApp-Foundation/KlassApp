<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Models\User;
use Illuminate\Console\Command;
use Laravel\Mcp\Server\Registrar;

/**
 * Starts the Google Classroom local MCP server for one user.
 *
 * The local MCP client is a stdio subprocess, so it has no HTTP auth session.
 * The web process passes that user's school id and user id. The tools then
 * read only that user's Google token.
 */
class StartGoogleClassroomMcpCommand extends Command
{
    protected $signature = 'google-classroom:mcp
        {school : KlassApp school id}
        {user : KlassApp user id. The server reads only this user\'s Google token}';

    protected $description = 'Start the Google Classroom MCP server for one user';

    public function handle(Registrar $registrar): int
    {
        $schoolId = (int) $this->argument('school');
        $userId = (int) $this->argument('user');
        $user = $userId > 0 ? User::query()->whereKey($userId)->where('school_id', $schoolId)->first() : null;

        if ($schoolId <= 0 || ! School::whereKey($schoolId)->exists() || $user === null) {
            $this->error('Pass a school id and a user id from that school.');

            return self::FAILURE;
        }

        config([
            'toshi.mcp_context.school_id' => $schoolId,
            'toshi.mcp_context.user_id' => $user->id,
        ]);

        $server = $registrar->getLocalServer('google-classroom');

        if ($server === null) {
            $this->error('Google Classroom MCP server is not registered.');

            return self::FAILURE;
        }

        $server();

        return self::SUCCESS;
    }
}
