<?php

namespace App\Services\Toshi;

use App\Models\User;
use Illuminate\Support\Collection;
use Laravel\Mcp\Client;
use Laravel\Mcp\Client\Schema\ToolResult;
use Laravel\Mcp\Facades\Mcp;
use LogicException;

/**
 * Convenience wrapper around named MCP clients with an explicit acting user.
 *
 * Named clients from Mcp::client() are already AuditingMcpClient / AuditingWebClient
 * (via AuditingMcpClientManager) — callTool audits at that layer. This class requires
 * an acting user up front so unauthenticated raw calls fail fast.
 *
 * Prefer spreading Mcp::client()->tools() into an agent (ToolInvoked path) when possible.
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
     * Invoke an MCP tool (audit written by AuditingMcpClient / AuditingWebClient).
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
                'ToshiMcpClient::callTool requires an acting user (pass actingUser or authenticate).'
            );
        }

        ToshiMcpCallAuditor::actingAs($user);

        try {
            return $this->client->callTool($name, $arguments);
        } finally {
            ToshiMcpCallAuditor::clearActingAs();
        }
    }

    /**
     * Expose the underlying (already auditing) named client for tools() / OAuth plumbing.
     */
    public function client(): Client
    {
        return $this->client;
    }
}
