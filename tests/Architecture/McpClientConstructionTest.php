<?php

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use SplFileInfo;

/**
 * Named MCP clients (Mcp::registerClient + AuditingMcpClientManager) are the only
 * audited construction path. Direct Client::web()/Client::local() skips the wrapper.
 *
 * Legitimate use: factory closures inside routes/ai.php (and test helpers that call
 * Mcp::registerClient). Nowhere under app/ or other route files.
 */
class McpClientConstructionTest extends TestCase
{
    private const FORBIDDEN_PATTERNS = [
        '/Client::web\s*\(/',
        '/Client::local\s*\(/',
        '/\\\\Laravel\\\\Mcp\\\\Client::web\s*\(/',
        '/\\\\Laravel\\\\Mcp\\\\Client::local\s*\(/',
        '/new\s+\\\\?Laravel\\\\Mcp\\\\WebClient\s*\(/',
        '/new\s+WebClient\s*\(/',
    ];

    /** @var list<string> */
    private const ALLOWLIST = [
        'routes/ai.php',
    ];

    #[Test]
    public function direct_mcp_client_construction_is_confined_to_named_client_registration(): void
    {
        $root = dirname(__DIR__, 2);
        $violations = [];

        foreach (['app', 'routes'] as $relativeDir) {
            $dir = $root.DIRECTORY_SEPARATOR.$relativeDir;
            if (! is_dir($dir)) {
                continue;
            }

            $iterator = new RegexIterator(
                new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)),
                '/\.php$/i'
            );

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                $path = $file->getPathname();
                $relative = ltrim(str_replace($root, '', $path), DIRECTORY_SEPARATOR);
                $relative = str_replace('\\', '/', $relative);

                if (in_array($relative, self::ALLOWLIST, true)) {
                    continue;
                }

                // Auditing wrappers extend Client — they must not call Client::web/local.
                $contents = file_get_contents($path);
                if ($contents === false) {
                    continue;
                }

                // Ignore docblocks / line comments so prose about the ban does not fail CI.
                $code = preg_replace('#/\*.*?\*/#s', '', $contents) ?? $contents;
                $code = preg_replace('#//.*$#m', '', $code) ?? $code;

                foreach (self::FORBIDDEN_PATTERNS as $pattern) {
                    if (preg_match($pattern, $code) === 1) {
                        $violations[] = $relative.' matched '.$pattern;
                    }
                }
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Direct MCP Client::web()/local() (or new WebClient) must only appear in routes/ai.php ".
            "inside Mcp::registerClient factories. Violations:\n".implode("\n", $violations)
        );
    }
}
