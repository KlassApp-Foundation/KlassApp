<?php

namespace App\Http\Middleware;

use App\Models\School;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * One central lockdown for the live demo's admin (users.is_public_demo),
 * whose password is public. For that account only:
 *
 *   - routes and Livewire components in config/public_demo.php are refused
 *     (site admin, own profile/password/email/2FA, impersonation, invites,
 *     broadcasts, fee reminders, contact numbers, uploads, imports, school
 *     settings, integrations, payments, Toshi);
 *   - any request carrying a file is refused;
 *   - a route-bound record (or a school id parameter) from another school
 *     answers 404;
 *   - the request is tagged in Context, so mail and notifications started by
 *     it (also from queued jobs) are cancelled (see AppServiceProvider).
 *
 * Every other user passes through untouched.
 */
class PublicDemoLockdown
{
    public const CONTEXT_KEY = 'public_demo_actor';

    public function handle(Request $request, Closure $next): Response
    {
        $user = self::actor();
        if (! $user) {
            return $next($request);
        }

        Context::addHidden(self::CONTEXT_KEY, (int) $user->id);

        if (($reason = self::refusal($request, $user)) !== null) {
            Log::warning('[public-demo] refused', ['reason' => $reason, 'path' => $request->path(), 'method' => $request->method()]);

            if ($reason === 'other school') {
                abort(404);
            }

            $message = "This isn't available in the public demo yet. It's coming soon.";
            // Livewire speaks JSON. A browser navigation gets the page.
            if ($request->expectsJson() || $request->is('livewire/*')) {
                return response()->json(['message' => $message]);
            }

            return response()->view('demo.public-demo-soon', ['message' => $message]);
        }

        return $next($request);
    }

    /** The signed-in public demo account, if any (session or API token). */
    public static function actor(): ?User
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            try {
                $user = Auth::guard('sanctum')->user();
            } catch (\Throwable) {
                $user = null;
            }
        }

        return $user instanceof User && $user->isPublicDemo() ? $user : null;
    }

    /** True while the public demo admin is acting (request, or a job it queued). */
    public static function actorActive(): bool
    {
        return Context::hasHidden(self::CONTEXT_KEY) || self::actor() !== null;
    }

    public static function refusal(Request $request, User $user): ?string
    {
        $path = ltrim($request->path(), '/');
        $write = ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);

        foreach ((array) config('public_demo.routes', []) as [$methods, $pattern, $reason]) {
            if (($methods === '*' || ($methods === 'write' && $write)) && preg_match($pattern, $path)) {
                return $reason;
            }
        }

        if ($request->allFiles() !== []) {
            return 'file upload';
        }

        if ($write && str_ends_with((string) $request->route()?->getName(), 'livewire.update')) {
            foreach ((array) $request->input('components', []) as $component) {
                $snapshot = json_decode((string) ($component['snapshot'] ?? ''), true);
                $name = (string) ($snapshot['memo']['name'] ?? '');
                foreach ((array) config('public_demo.livewire', []) as [$pattern, $reason]) {
                    if ($name !== '' && preg_match($pattern, $name)) {
                        return $reason;
                    }
                }
            }
        }

        $schoolId = (int) $user->school_id;
        foreach ((array) $request->route()?->parameters() as $key => $value) {
            if ($value instanceof School) {
                if ((int) $value->getKey() !== $schoolId) {
                    return 'other school';
                }
            } elseif ($value instanceof Model) {
                $owner = $value->getAttribute('school_id');
                if ($owner !== null && (int) $owner !== $schoolId) {
                    return 'other school';
                }
            } elseif (preg_match('/^school(_?id)?$/i', (string) $key) && is_numeric($value) && (int) $value !== $schoolId) {
                return 'other school';
            }
        }

        return null;
    }
}
