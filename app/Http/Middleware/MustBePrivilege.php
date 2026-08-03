<?php

namespace App\Http\Middleware;

use App\Helpers\OnboardingHelper;
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

        $isDashboard = $request->is('admin/dashboard') || $request->is('admin/dashboard/*');
        $isLivewire = $request->is('livewire/*');
        $isAcademicSetupRoute = $request->is('admin/academics') || $request->is('admin/academic/*');
        $isStandardSetupRoute = $request->is('admin/standard/create') || $request->is('admin/standard/add');

        // Incomplete SaaS onboarding: keep admins on dashboard (continue-setup + Toshi),
        // never bounce them into pages that assume AcademicYear/standards exist.
        $onboardingIncomplete = $schoolId && OnboardingHelper::hasMissingSteps((int) $schoolId, \Auth::id());

        if ($academicYear === null) {
            if ($isDashboard || $isLivewire || $isAcademicSetupRoute) {
                return $next($request);
            }

            return redirect('/admin/dashboard?toshi_onboarding=1')
                ->with('open_toshi_onboarding', true);
        }

        if ($standardCount === 0) {
            if ($isDashboard || $isLivewire || $isStandardSetupRoute || $isAcademicSetupRoute || $onboardingIncomplete) {
                return $next($request);
            }

            if ($onboardingIncomplete) {
                return redirect('/admin/dashboard?toshi_onboarding=1')
                    ->with('open_toshi_onboarding', true);
            }

            return redirect('/admin/standard/create');
        }

        return $next($request);
    }
}
