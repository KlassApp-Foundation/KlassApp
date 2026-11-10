<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Picks the school guide for the page the person was on.
 *
 * The account menu passes the current route name. The first matching pattern
 * in config/help.php wins. Anything else opens the help home.
 */
class HelpGuide
{
    public static function urlFor(?string $routeName): string
    {
        $fallback = (string) config('help.fallback', '/docs-preview/help/');
        $relative = self::relativePath($routeName);

        if ($relative === null) {
            return $fallback;
        }

        return '/docs-preview/help/'.ltrim($relative, '/');
    }

    private static function relativePath(?string $routeName): ?string
    {
        if (! is_string($routeName) || preg_match('/\A[A-Za-z0-9._*-]+\z/', $routeName) !== 1) {
            return null;
        }

        $guides = config('help.guides', []);
        if (! is_array($guides)) {
            return null;
        }

        foreach ($guides as $pattern => $path) {
            if (! is_string($pattern) || ! is_string($path) || ! Str::is($pattern, $routeName)) {
                continue;
            }

            if (! str_starts_with($path, 'guides/')) {
                continue;
            }

            return $path;
        }

        return null;
    }
}
