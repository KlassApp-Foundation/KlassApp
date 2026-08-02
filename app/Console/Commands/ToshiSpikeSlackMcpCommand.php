<?php

namespace App\Console\Commands;

use App\Ai\Agents\SpikeSlackMcpTestAgent;
use App\Listeners\Toshi\LogToolInvoked;
use App\Services\Toshi\ToshiMcpClient;
use App\Services\ToshiAuditService;
use Illuminate\Console\Command;
use Laravel\Ai\Tools\McpTool;
use Laravel\Mcp\Facades\Mcp;
use ReflectionMethod;
use Throwable;

/**
 * End-to-end probe for Mcp::client('slack')->tools() plumbing.
 *
 * Default mode uses the local SpikeSlackMockServer (no Slack credentials).
 * Live mode (SLACK_MCP_MODE=live) targets https://mcp.slack.com/mcp when tokens exist.
 *
 * Prefer agent-mediated tools (ToolInvoked → LogToolInvoked) or ToshiMcpClient::callTool.
 * Raw Mcp::client()->callTool() is banned for app code (bypasses audit).
 * MCP Approvable/HITL for write tools is deferred.
 */
class ToshiSpikeSlackMcpCommand extends Command
{
    protected $signature = 'toshi:spike-slack-mcp
                            {--call : Invoke spike-slack-auth-test via ToshiMcpClient (audited)}
                            {--agent : Resolve SpikeSlackMcpTestAgent and list wrapped tools}
                            {--audit : Document reconciled ToolInvoked audit path}';

    protected $description = 'Spike: Mcp::client(slack) list/call tools (mock by default)';

    public function handle(): int
    {
        $mode = config('services.slack_mcp.mode', 'mock');
        $this->info("SLACK_MCP_MODE={$mode}");
        $this->line('Named client: slack');

        try {
            $client = Mcp::client('slack');
            $this->info('Resolved Mcp::client(\'slack\')');
        } catch (Throwable $e) {
            $this->error('Failed to resolve client: '.$e->getMessage());

            return self::FAILURE;
        }

        try {
            $tools = $client->tools();
        } catch (Throwable $e) {
            $this->error('tools() failed: '.$e->getMessage());
            $this->warn('If mode=live: need Slack app OAuth / token.');

            return self::FAILURE;
        }

        $this->info('tools() count: '.$tools->count());
        foreach ($tools as $name => $tool) {
            $this->line(sprintf(
                '  - %s | %s',
                is_string($name) ? $name : ($tool->name ?? '?'),
                $tool->description ?? $tool->title ?? ''
            ));
        }

        if ($this->option('call')) {
            $this->callFirstReadOnlyToolAudited($tools);
        }

        if ($this->option('agent')) {
            $this->probeAgentTools();
        }

        if ($this->option('audit')) {
            $this->probeAuditCapture();
        }

        return self::SUCCESS;
    }

    /**
     * @param  \Illuminate\Support\Collection<string, mixed>  $tools
     */
    private function callFirstReadOnlyToolAudited($tools): void
    {
        $preferred = ['spike-slack-auth-test', 'auth_test', 'slack_auth_test'];
        $name = null;

        foreach ($preferred as $candidate) {
            if ($tools->has($candidate)) {
                $name = $candidate;
                break;
            }
        }

        if ($name === null) {
            $name = $tools->keys()->first();
        }

        if ($name === null) {
            $this->warn('No tools to call.');

            return;
        }

        $this->info("ToshiMcpClient::callTool({$name})…");

        try {
            $result = ToshiMcpClient::named('slack')->callTool($name, []);
            $this->line('isError: '.(($result->isError ?? false) ? 'yes' : 'no'));
            $this->line((string) $result);
        } catch (Throwable $e) {
            $this->error('audited callTool failed: '.$e->getMessage());
            $this->warn('Pass an authenticated user or -- no auth in CLI: use actingUser in code.');
        }
    }

    private function probeAgentTools(): void
    {
        $agent = new SpikeSlackMcpTestAgent;
        $tools = iterator_to_array($agent->tools(), false);
        $this->info('SpikeSlackMcpTestAgent tools(): '.count($tools));

        foreach ($tools as $tool) {
            $label = $tool::class;
            if ($tool instanceof McpTool) {
                $label .= ' name='.$tool->name();
            } elseif (is_object($tool) && property_exists($tool, 'name')) {
                $label .= ' mcp='.$tool->name;
            }
            $this->line('  - '.$label);
        }
    }

    private function probeAuditCapture(): void
    {
        $this->newLine();
        $this->info('Audit capture (reconciled):');
        $this->line('- Agent-mediated tools (native + McpTool) → ToolInvoked → LogToolInvoked → ToshiAuditService');
        $this->line('- Raw Mcp::client()->callTool() bypasses ToolInvoked — banned; use ToshiMcpClient');
        $this->line('- MCP Approvable/HITL for write tools is deferred');

        $ref = new ReflectionMethod(ToshiAuditService::class, 'logExecution');
        $this->line('ToshiAuditService::logExecution: '.$ref->getFileName().':'.$ref->getStartLine());

        $listener = new ReflectionMethod(LogToolInvoked::class, 'handle');
        $this->line('LogToolInvoked::handle: '.$listener->getFileName().':'.$listener->getStartLine());

        $mcpToolPath = (new \ReflectionClass(McpTool::class))->getFileName();
        $this->line('Laravel\\Ai\\Tools\\McpTool: '.$mcpToolPath);
    }
}
