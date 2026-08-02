<?php

namespace App\Services\Toshi;

use App\Services\Toshi\Concerns\AuditsMcpToolCalls;
use Closure;
use Laravel\Mcp\Client;
use Laravel\Mcp\Client\Contracts\Transport;

/**
 * Stdio/local MCP client whose callTool always runs ToshiMcpCallAuditor.
 */
class AuditingMcpClient extends Client
{
    use AuditsMcpToolCalls;

    public static function wrap(Client $client): self
    {
        if ($client instanceof self) {
            return $client;
        }

        /** @var Transport $transport */
        $transport = Closure::bind(fn () => $this->transport, $client, Client::class)();
        /** @var string|null $name */
        $name = Closure::bind(fn () => $this->name, $client, Client::class)();

        $wrapped = new self($transport, $client->clientInfo);

        if ($name !== null) {
            $wrapped->setName($name);
        }

        return $wrapped;
    }
}
