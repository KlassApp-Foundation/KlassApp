<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Data processing agreement gate
    |--------------------------------------------------------------------------
    |
    | When enabled, a real school must confirm the data processing agreement
    | before any onboarding write. Demo and test schools are exempt either way.
    |
    | Off by default: the acceptance text is not approved yet, so the product
    | must not ask a school to accept it. Turn this on only after the approved
    | wording exists and ONBOARDING_DPA_VERSION names it.
    |
    */

    'dpa_gate' => [
        'enabled' => (bool) env('ONBOARDING_DPA_GATE_ENABLED', false),

        // Placeholder on purpose. Replace with the approved document's version
        // identifier when the text is signed off; rows already recorded keep
        // the version they were accepted under.
        'version' => env('ONBOARDING_DPA_VERSION', 'placeholder-pending-approval'),
    ],

];
