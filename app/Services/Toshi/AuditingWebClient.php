<?php

namespace App\Services\Toshi;

use App\Services\Toshi\Concerns\AuditsMcpToolCalls;
use Closure;
use Laravel\Mcp\Client;
use Laravel\Mcp\Client\OAuth\OAuthConfig;
use Laravel\Mcp\Client\Transport\HttpTransport;
use Laravel\Mcp\WebClient;

/**
 * HTTP MCP client whose callTool always runs ToshiMcpCallAuditor.
 * Preserves WebClient token/OAuth configuration from the factory return value.
 */
class AuditingWebClient extends WebClient
{
    use AuditsMcpToolCalls;

    public static function wrap(WebClient $client): self
    {
        if ($client instanceof self) {
            return $client;
        }

        /** @var HttpTransport $httpTransport */
        $httpTransport = Closure::bind(fn () => $this->httpTransport, $client, WebClient::class)();
        /** @var OAuthConfig|null $oAuthConfig */
        $oAuthConfig = Closure::bind(fn () => $this->oAuthConfig, $client, WebClient::class)();
        /** @var string|null $name */
        $name = Closure::bind(fn () => $this->name, $client, Client::class)();

        $wrapped = new self($httpTransport, $client->clientInfo, $oAuthConfig);

        if ($name !== null) {
            $wrapped->setName($name);
        }

        return $wrapped;
    }
}
