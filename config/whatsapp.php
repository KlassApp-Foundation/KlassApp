<?php

/**
 * WhatsApp template approval record.
 *
 * The app never asks Meta whether a template is approved. This list is the
 * record of what Meta has approved, written by whoever sees the approval in
 * the WhatsApp manager. It is empty by default, so every gated send is
 * refused until the approval is recorded.
 *
 * Set WHATSAPP_APPROVED_TEMPLATES to a comma-separated list, for example:
 *   WHATSAPP_APPROVED_TEMPLATES="results_released,fee_update"
 */
return [
    'approved_templates' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('WHATSAPP_APPROVED_TEMPLATES', '')),
    ))),
];
