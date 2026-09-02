<?php

/**
 * Create (or update) and publish the parent-link-request WhatsApp Flow on Meta.
 *
 * Usage:
 *   php scripts/publish-whatsapp-parent-link-flow.php
 *
 * Optional env overrides (falls back to Laravel config / .env):
 *   TOKEN=...              Meta system user token (overrides WHATSAPP_BUSINESS_API_TOKEN)
 *   WHATSAPP_BUSINESS_WABA_ID=...
 *
 * On success prints the published flow ID — add to .env as WHATSAPP_PARENT_LINK_FLOW_ID.
 */
declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = getenv('TOKEN') ?: config('services.whatsapp.business_api_token');
$wabaId = getenv('WHATSAPP_BUSINESS_WABA_ID') ?: config('services.whatsapp.business_waba_id');
$apiVersion = config('services.whatsapp.business_api_version', 'v21.0');
$flowName = 'parent_link_request';
$flowJsonPath = resource_path('whatsapp/flows/parent-link-request.json');

if (empty($token)) {
    fwrite(STDERR, "Error: WHATSAPP_BUSINESS_API_TOKEN (or TOKEN env) is not set.\n");
    exit(1);
}

if (empty($wabaId)) {
    fwrite(STDERR, "Error: WHATSAPP_BUSINESS_WABA_ID is not set.\n");
    exit(1);
}

if (! is_readable($flowJsonPath)) {
    fwrite(STDERR, "Error: Flow JSON not found at {$flowJsonPath}\n");
    exit(1);
}

$flowJson = file_get_contents($flowJsonPath);
if ($flowJson === false) {
    fwrite(STDERR, "Error: Could not read {$flowJsonPath}\n");
    exit(1);
}

json_decode($flowJson, true, 512, JSON_THROW_ON_ERROR);

$base = "https://graph.facebook.com/{$apiVersion}";

/** @var \Illuminate\Http\Client\Response $listResp */
$listResp = Http::withToken($token)->get("{$base}/{$wabaId}/flows", [
    'fields' => 'id,name,status',
    'limit' => 50,
]);

if (! $listResp->successful()) {
    fwrite(STDERR, "Failed to list flows: HTTP {$listResp->status()}\n{$listResp->body()}\n");
    exit(1);
}

$existingId = null;
foreach ($listResp->json('data') ?? [] as $flow) {
    if (($flow['name'] ?? '') === $flowName) {
        $existingId = $flow['id'] ?? null;
        break;
    }
}

if ($existingId) {
    $flowId = $existingId;
    echo "Reusing existing flow {$flowId} ({$flowName})\n";
} else {
    $createResp = Http::withToken($token)->post("{$base}/{$wabaId}/flows", [
        'name' => $flowName,
        'categories' => ['OTHER'],
    ]);

    if (! $createResp->successful()) {
        fwrite(STDERR, "Failed to create flow: HTTP {$createResp->status()}\n{$createResp->body()}\n");
        exit(1);
    }

    $flowId = $createResp->json('id');
    echo "Created flow {$flowId}\n";
}

$assetResp = Http::withToken($token)
    ->attach('file', $flowJson, 'flow.json')
    ->post("{$base}/{$flowId}/assets", [
        'name' => 'flow.json',
        'asset_type' => 'FLOW_JSON',
    ]);

if (! $assetResp->successful()) {
    fwrite(STDERR, "Failed to upload FLOW_JSON asset: HTTP {$assetResp->status()}\n{$assetResp->body()}\n");
    exit(1);
}

echo "Uploaded flow JSON asset\n";

$validateResp = Http::withToken($token)->post("{$base}/{$flowId}/validate");
if ($validateResp->successful()) {
    $validation = $validateResp->json();
    if (! empty($validation['validation_errors'])) {
        fwrite(STDERR, "Flow validation errors:\n".json_encode($validation, JSON_PRETTY_PRINT)."\n");
        exit(1);
    }
    echo "Flow JSON validated OK\n";
} else {
    echo "Validate endpoint returned HTTP {$validateResp->status()} — continuing to publish\n";
}

$publishResp = Http::withToken($token)->post("{$base}/{$flowId}/publish");
if (! $publishResp->successful()) {
    fwrite(STDERR, "Failed to publish flow: HTTP {$publishResp->status()}\n{$publishResp->body()}\n");
    exit(1);
}

$statusResp = Http::withToken($token)->get("{$base}/{$flowId}", [
    'fields' => 'id,name,status,validation_errors',
]);

echo "Published flow {$flowId}\n";
echo json_encode($statusResp->json(), JSON_PRETTY_PRINT)."\n\n";
echo "Add to .env:\nWHATSAPP_PARENT_LINK_FLOW_ID={$flowId}\n";
