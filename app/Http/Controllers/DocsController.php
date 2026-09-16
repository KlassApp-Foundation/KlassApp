<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Serves the public Docsify documentation tree from base_path('docs').
 *
 * Only an allowlisted surface is exposed. Sensitive working notes that live
 * under docs/ in git (evidence/, *audit*.md, IDOR notes, screenshot dumps,
 * od-mocks/, etc.) must not be reachable over HTTP.
 */
class DocsController
{
    /**
     * Directory prefixes under docs/ that may be served.
     *
     * @var list<string>
     */
    private const PUBLIC_PREFIXES = [
        'community/',
        'dev/',
        'shared/',
        'readme/',
    ];

    /**
     * Exact files allowed at the docs/ root (Docsify hub + canonical pages).
     *
     * @var list<string>
     */
    private const PUBLIC_ROOT_FILES = [
        'index.html',
        '_sidebar.md',
        'README.md',
        'roadmap.md',
        'architecture.md',
    ];

    /**
     * Absolute deny prefixes (defense in depth if allowlist is ever widened).
     *
     * @var list<string>
     */
    private const DENIED_PREFIXES = [
        'evidence/',
        'internal/',
        'archive/',
        'od-mocks/',
        'bugfix-ay-whatsapp/',
        'dashboard-empty-state-screenshots/',
        'empty-state-demo-screenshots/',
        'wave1-screenshots/',
        'wave2-screenshots/',
        'wave3-screenshots/',
        'wizard-bulk-upload/',
        'wizard-review-preview/',
        'wizard-toshi-sync-screenshots/',
    ];

    /**
     * @var array<string, string>
     */
    private const MIMES = [
        'md' => 'text/markdown; charset=utf-8',
        'svg' => 'image/svg+xml',
        'css' => 'text/css; charset=utf-8',
        'js' => 'application/javascript; charset=utf-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'html' => 'text/html; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'txt' => 'text/plain; charset=utf-8',
    ];

    public function __invoke(?string $path = null): Response
    {
        $docsRoot = realpath(base_path('docs'));
        if ($docsRoot === false) {
            throw new NotFoundHttpException;
        }

        $relative = $this->normalizePath($path);

        if ($relative === '' || str_ends_with($relative, '/')) {
            return $this->spaFallback($docsRoot, rtrim($relative, '/'));
        }

        if (! $this->isPublicPath($relative)) {
            throw new NotFoundHttpException;
        }

        $absolute = realpath($docsRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative));
        if ($absolute === false || ! $this->isInsideDocsRoot($docsRoot, $absolute) || ! is_file($absolute)) {
            return $this->spaFallback($docsRoot, $relative);
        }

        return $this->fileResponse($absolute);
    }

    private function normalizePath(?string $path): string
    {
        $path = trim((string) $path, '/');
        if ($path === '' || $path === '.') {
            return '';
        }

        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                throw new NotFoundHttpException;
            }
            $segments[] = $segment;
        }

        return implode('/', $segments);
    }

    private function isPublicPath(string $relative): bool
    {
        $lower = strtolower($relative);

        foreach (self::DENIED_PREFIXES as $denied) {
            if (str_starts_with($lower, $denied) || $lower === rtrim($denied, '/')) {
                return false;
            }
        }

        foreach (self::PUBLIC_PREFIXES as $prefix) {
            if (str_starts_with($relative, $prefix) || $relative === rtrim($prefix, '/')) {
                return true;
            }
        }

        return in_array($relative, self::PUBLIC_ROOT_FILES, true);
    }

    private function isInsideDocsRoot(string $docsRoot, string $absolute): bool
    {
        $docsRoot = rtrim($docsRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        return str_starts_with($absolute.DIRECTORY_SEPARATOR, $docsRoot)
            || $absolute === rtrim($docsRoot, DIRECTORY_SEPARATOR);
    }

    private function spaFallback(string $docsRoot, string $relative): Response
    {
        if (str_starts_with($relative, 'community')) {
            $index = $docsRoot.DIRECTORY_SEPARATOR.'community'.DIRECTORY_SEPARATOR.'index.html';
        } elseif (str_starts_with($relative, 'dev')) {
            $index = $docsRoot.DIRECTORY_SEPARATOR.'dev'.DIRECTORY_SEPARATOR.'index.html';
        } else {
            $index = $docsRoot.DIRECTORY_SEPARATOR.'index.html';
        }

        if (! is_file($index)) {
            throw new NotFoundHttpException;
        }

        return $this->fileResponse($index);
    }

    private function fileResponse(string $absolute): Response
    {
        $ext = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
        $mime = self::MIMES[$ext] ?? 'application/octet-stream';

        return response(file_get_contents($absolute), 200, [
            'Content-Type' => $mime,
        ]);
    }
}
