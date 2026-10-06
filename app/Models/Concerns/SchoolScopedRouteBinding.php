<?php

namespace App\Models\Concerns;

/**
 * School-scopes implicit route model bindings.
 *
 * When a route parameter is resolved for an authenticated school user
 * (SubstituteBindings), the record must belong to that user's school —
 * anything else resolves to null, which the router turns into a 404.
 *
 * Skipped, so non-web callers keep working unchanged, for:
 *   - console commands and queue workers (artisan / workers);
 *   - unauthenticated requests;
 *   - site admins (usergroup 1).
 *
 * Only apply this to models whose records always belong to exactly one school
 * (a school_id column that is never legitimately global). Roles that can
 * legitimately span schools (for example a parent with children in two
 * schools) must not resolve the models this trait is applied to.
 */
trait SchoolScopedRouteBinding
{
    /**
     * Scope the route binding query to the acting user's school when the
     * context requires it, then fall through to the default binding.
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        if ($this->routeBindingShouldBeSchoolScoped()) {
            $query = $query->where($this->getTable().'.school_id', auth()->user()->school_id);
        }

        return $query->where($field ?? $this->getRouteKeyName(), $value);
    }

    /**
     * Whether the current context must scope route bindings by school.
     */
    protected function routeBindingShouldBeSchoolScoped(): bool
    {
        // Console commands and queue workers resolve freely.
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return false;
        }

        $user = auth()->user();

        if ($user === null) {
            return false; // unauthenticated
        }

        if ((int) $user->usergroup_id === 1) {
            return false; // site admin
        }

        return true;
    }
}
