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

        // Manual onboarding surface: pages a self-service admin needs to finish
        // setup WITHOUT Toshi, before an AcademicYear / standards exist. These must
        // stay reachable in both pre-AY and pre-standards gates so the manual path
        // has full parity with the Toshi-assisted path.
        $isManualOnboardingRoute = $this->isManualOnboardingRoute($request);

        // Incomplete SaaS onboarding: keep admins on dashboard (continue-setup + Toshi),
        // never bounce them into pages that assume AcademicYear/standards exist.
        $onboardingIncomplete = $schoolId && OnboardingHelper::hasMissingSteps((int) $schoolId, \Auth::id());

        if ($academicYear === null) {
            if ($isDashboard || $isLivewire || $isAcademicSetupRoute
                || $isStandardSetupRoute || $isManualOnboardingRoute) {
                return $next($request);
            }

            return redirect('/admin/dashboard')
                ->with('open_toshi_onboarding', true);
        }

        if ($standardCount === 0) {
            if ($isDashboard || $isLivewire || $isStandardSetupRoute || $isAcademicSetupRoute
                || $isManualOnboardingRoute || $onboardingIncomplete) {
                return $next($request);
            }

            return redirect('/admin/standard/create');
        }

        return $next($request);
    }

    /**
     * Routes that make up the manual (non-Toshi) onboarding surface: School
     * Details (curriculum / country / EMIS / UNEB centre), WhatsApp phone
     * linking, and the informational subscription / plan pages.
     *
     * Deliberately narrow — only pages required to finish onboarding by hand.
     * Everything else stays gated behind AcademicYear + standards.
     */
    private function isManualOnboardingRoute($request): bool
    {
        return $request->is('admin/schooldetails')
            || $request->is('admin/schooldetails/*')
            || $request->is('admin/whatsapp/phone')
            || $request->is('admin/subscriptions')
            || $request->is('admin/subscriptions/*')
            || $request->is('admin/onboarding/wizard')
            || $request->is('admin/onboarding/wizard/*');
    }
}
