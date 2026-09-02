<?php

/**
 * Send the parent-link-request WhatsApp Flow to a phone (live Graph API test).
 *
 * Usage:
 *   php scripts/send-whatsapp-parent-link-flow.php +256700000000
 *
 * Requires WHATSAPP_BUSINESS_API_TOKEN, WHATSAPP_BUSINESS_PHONE_NUMBER_ID,
 * and WHATSAPP_PARENT_LINK_FLOW_ID in .env (or production config).
 */
declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$phone = $argv[1] ?? null;
if (empty($phone)) {
    fwrite(STDERR, "Usage: php scripts/send-whatsapp-parent-link-flow.php +256...\n");
    exit(1);
}

/** @var \App\Services\WhatsAppBusinessService $wa */
$wa = app(\App\Services\WhatsAppBusinessService::class);

if (! $wa->isConfigured()) {
    fwrite(STDERR, "WhatsApp Business API is not configured.\n");
    exit(1);
}

$result = $wa->sendParentLinkRequestFlow($phone);

echo json_encode($result, JSON_PRETTY_PRINT)."\n";
exit(($result['success'] ?? false) ? 0 : 1);
