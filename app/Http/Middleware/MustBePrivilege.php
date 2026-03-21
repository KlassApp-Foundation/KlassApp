<?php

namespace App\Http\Middleware;

use App\Helpers\SiteHelper;
use App\Models\Standard;
use Closure;

class MustBePrivilege
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $schoolId = \Auth::user()->school_id;
        $academicYear = SiteHelper::getAcademicYear($schoolId);
        $standardCount = Standard::where('school_id', $schoolId)->count();

        // Allow onboarding routes to avoid redirect loops for first-time schools.
        $isAcademicSetupRoute = $request->is('admin/academics') || $request->is('admin/academic/*');
        $isStandardSetupRoute = $request->is('admin/standard/create') || $request->is('admin/standard/add');

        if ($academicYear === null) {
            if ($isAcademicSetupRoute) {
                return $next($request);
            }

            return redirect('/admin/academics');
        }

        if ($standardCount === 0) {
            if ($isStandardSetupRoute || $isAcademicSetupRoute) {
                return $next($request);
            }

            return redirect('/admin/standard/create');
        }

        return $next($request);

        abort(404);
    }
}
