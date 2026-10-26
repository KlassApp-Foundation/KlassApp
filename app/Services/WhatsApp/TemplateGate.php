<?php

namespace App\Services\WhatsApp;

/**
 * The one place that answers "may this template be sent?".
 *
 * A school cannot send a template Meta has not approved, and the app cannot ask
 * Meta, so the answer comes from a recorded list (config/whatsapp.php). The
 * default is an empty list: fail-closed, never fail-open.
 */
final class TemplateGate
{
    /** The results template a marks release uses outside the 24-hour window. */
    public const RESULTS_RELEASED = 'results_released';

    public function approved(string $name): bool
    {
        $approved = (array) config('whatsapp.approved_templates', []);

        return in_array($name, array_map('strval', $approved), true);
    }

    /** The reason a gated send is refused, or null when the template is approved. */
    public function blocker(string $name): ?string
    {
        return $this->approved($name) ? null : $name . ' is not approved by Meta yet';
    }
}
