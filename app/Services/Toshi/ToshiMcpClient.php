<?php

namespace App\Services\Toshi;

use App\Models\School;
use App\Models\User;
use App\Services\ToshiAuditService;
use Illuminate\Support\Collection;
use Laravel\Mcp\Client;
use Laravel\Mcp\Client\Schema\ToolResult;
use Laravel\Mcp\Facades\Mcp;
use LogicException;

/**
 * Audited wrapper around Laravel MCP client callTool.
 *
 * Agent-mediated MCP tools already audit via LogToolInvoked (ToolInvoked event).
 * Raw Mcp::client()->callTool() bypasses that event loop — use this wrapper instead,
 * or prefer spreading Mcp::client()->tools() into an agent so ToolInvoked fires.
 *
 * MCP Approvable/HITL for write tools is deferred.
 */
class ToshiMcpClient
{
    public function __construct(
        private Client $client,
        private ?User $actingUser = null,
    ) {}

    public static function named(string $name, ?User $actingUser = null): self
    {
        return new self(Mcp::client($name), $actingUser);
    }

    /**
     * Discover tools — safe; does not invoke or audit.
     *
     * @return Collection<string, mixed>
     */
    public function tools(?int $limit = null): Collection
    {
        return $limit === null
            ? $this->client->tools()
            : $this->client->tools(limit: $limit);
    }

    /**
     * Invoke an MCP tool and write a Toshi audit row (read-shape: acting_user_id, null approver_id).
     *
     * @param  array<string, mixed>  $arguments
     */
    public function callTool(string $name, array $arguments = []): ToolResult
    {
        $user = $this->actingUser
            ?? (auth()->user() instanceof User ? auth()->user() : null)
            ?? (request()->user() instanceof User ? request()->user() : null);

        if (! $user instanceof User) {
            throw new LogicException(
                'ToshiMcpClient::callTool requires an acting user (pass actingUser or authenticate). '.
                'Raw Mcp::client()->callTool() is banned for app code — it bypasses Toshi audit.'
            );
        }

        $result = $this->client->callTool($name, $arguments);

        $school = $user->school_id
            ? School::find($user->school_id)
            : null;

        $resultText = method_exists($result, 'text')
            ? (string) $result->text()
            : (string) $result;

        if (($result->isError ?? false) === true) {
            $resultText = str_starts_with($resultText, '❌')
                ? $resultText
                : '❌ '.$resultText;
        }

        ToshiAuditService::logExecution(
            user: $user,
            school: $school,
            toolName: 'mcp_tools_'.$name,
            arguments: $arguments,
            result: $resultText,
            approver: null,
            actingUser: $user,
        );

        return $result;
    }

    /**
     * Expose the underlying client only for tools() / OAuth plumbing — not for unaudited callTool.
     */
    public function client(): Client
    {
        return $this->client;
    }
}
