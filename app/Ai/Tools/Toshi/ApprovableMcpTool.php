<?php

namespace App\Ai\Tools\Toshi;

use App\Services\Toshi\McpWriteGate;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Approvals\Approval;
use Laravel\Ai\Concerns\InteractsWithApprovals;
use Laravel\Ai\Contracts\Approvable;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\McpTool;
use Laravel\Ai\Tools\Request;

/**
 * MCP tool wrapper that gates write-classified tools behind the Approvable
 * approval mechanism. Read-classified tools pass through immediately.
 *
 * Exposed by Skills via `tools()` instead of raw MCP primitives so the
 * laravel/ai loop at TextGenerationLoop::approvalForTool() sees an Approvable
 * instance and pauses for human decision before any write executes.
 *
 * Architecture invariant: no new Client construction sites; the wrapped
 * primitive's `call()` hits the existing AuditingMcpClient → audit row.
 */
class ApprovableMcpTool implements Approvable, Tool
{
    use InteractsWithApprovals;

    private McpTool $delegate;

    private string $clientName;

    public function __construct(string $clientName, object $primitive)
    {
        if (! McpTool::supports($primitive)) {
            throw new \InvalidArgumentException('Primitive must be an MCP client Tool instance.');
        }

        $this->clientName = $clientName;
        $this->delegate = new McpTool($primitive);
    }

    public static function wrap(string $clientName, object $primitive): self
    {
        return new self($clientName, $primitive);
    }

    public function name(): string
    {
        return $this->delegate->name();
    }

    public function description(): string
    {
        return $this->delegate->description();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->delegate->schema($schema);
    }

    public function handle(Request $request): string
    {
        return McpWriteGate::bypassFor(fn () => $this->delegate->handle($request));
    }

    protected function needsApproval(Request $request): Approval|bool
    {
        $toolName = $this->rawToolName();

        if (! McpWriteGate::isWrite($this->clientName, $toolName)) {
            return false;
        }

        $summary = $this->summarizeArguments($request);

        return Approval::required(
            "Approve {$this->clientName} write: {$toolName}{$summary}"
        );
    }

    private function rawToolName(): string
    {
        $name = $this->delegate->name();

        return str_starts_with($name, 'mcp_tools_')
            ? substr($name, 10)
            : $name;
    }

    /**
     * @param  array<string, mixed>  $request
     */
    private function summarizeArguments(Request $request): string
    {
        $args = $request->all();

        if ($args === []) {
            return '';
        }

        $pairs = [];

        foreach (array_slice($args, 0, 3, true) as $key => $value) {
            $val = is_array($value) ? '[...]' : (string) $value;
            $val = mb_strlen($val) > 40 ? mb_substr($val, 0, 40).'…' : $val;
            $pairs[] = "{$key}={$val}";
        }

        $summary = implode(', ', $pairs);

        return " ({$summary})";
    }
}
