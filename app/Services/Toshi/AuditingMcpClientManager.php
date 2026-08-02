<?php

namespace App\Services\Toshi;

use Laravel\Mcp\Client;
use Laravel\Mcp\Client\ClientManager;
use Laravel\Mcp\WebClient;

/**
 * Ensures Mcp::client($name) / ClientManager::build() always return an auditing client.
 *
 * This structurally closes the raw Mcp::client()->callTool() audit bypass for named clients.
 * Direct Client::web()/Client::local() is forbidden outside routes/ai.php registration
 * factories (enforced by Tests\Architecture\McpClientConstructionTest).
 */
class AuditingMcpClientManager extends ClientManager
{
    public function build(string $name): Client
    {
        return $this->wrap(parent::build($name));
    }

    private function wrap(Client $client): Client
    {
        if ($client instanceof AuditingMcpClient || $client instanceof AuditingWebClient) {
            return $client;
        }

        if ($client instanceof WebClient) {
            return AuditingWebClient::wrap($client);
        }

        return AuditingMcpClient::wrap($client);
    }
}
