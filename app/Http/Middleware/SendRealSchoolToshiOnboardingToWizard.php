<?php

namespace App\Http\Middleware;

use App\Services\Toshi\ToshiOnboardingPath;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A real school already in Toshi onboarding continues in the Wizard.
 * Livewire updates stay put; the next full page leaves the chat path.
 */
class SendRealSchoolToshiOnboardingToWizard
{
    public function __construct(private ToshiOnboardingPath $paths)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($redirect = $this->redirect($request)) {
            return $redirect;
        }

        return $next($request);
    }

    public function redirect(Request $request): ?Response
    {
        $school = $request->user()?->school;

        if ($school === null
            || ! $this->paths->mustUseWizard($school)
            || $request->routeIs('admin.onboarding.wizard')
            || $request->is('livewire/*')) {
            return null;
        }

        return $this->paths->sendToWizard($school);
    }
}
