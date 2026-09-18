<?php

namespace App\Services\Toshi;

class McpWriteGate
{
    private const MODES = ['deny', 'classify', 'allowlist'];

    private static bool $bypass = false;

    public static function bypassFor(callable $callback): mixed
    {
        $previous = self::$bypass;
        self::$bypass = true;

        try {
            return $callback();
        } finally {
            self::$bypass = $previous;
        }
    }

    public static function isWrite(string $clientName, string $toolName): bool
    {
        if (! config('toshi.mcp_write_gates.master_switch', false)) {
            return false;
        }

        $gateConfig = config("toshi.mcp_write_gates.connectors.{$clientName}");

        if ($gateConfig === null) {
            return true;
        }

        $mode = $gateConfig['mode'] ?? 'deny';

        if (! in_array($mode, self::MODES, true)) {
            return true;
        }

        $catalog = config("toshi.mcp_connectors.{$clientName}");

        if ($catalog === null) {
            return true;
        }

        $readTools = $catalog['read_tools'] ?? [];
        $writeTools = $catalog['write_tools'] ?? [];

        return match ($mode) {
            'deny' => true,
            'classify' => ! in_array($toolName, $readTools, true),
            'allowlist' => in_array($toolName, $writeTools, true),
            default => true,
        };
    }

    public static function assertExecutable(string $clientName, string $toolName): void
    {
        if (self::$bypass) {
            return;
        }

        if (static::isWrite($clientName, $toolName)) {
            throw new \RuntimeException(
                "MCP tool [{$toolName}] on client [{$clientName}] is write-classified. "
                .'Execute only through an approved agent turn.'
            );
        }
    }
}
