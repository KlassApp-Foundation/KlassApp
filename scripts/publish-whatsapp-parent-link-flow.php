<?php

/**
 * Create (or update) and publish the parent-link-request WhatsApp Flow on Meta.
 *
 * Usage:
 *   # Dry run — list flows, validate JSON locally, print what would happen (no Meta writes):
 *   php scripts/publish-whatsapp-parent-link-flow.php --dry-run
 *
 *   # Real run — uploads JSON, validates on Meta, publishes:
 *   php scripts/publish-whatsapp-parent-link-flow.php
 *
 * Optional env overrides (falls back to Laravel config / .env):
 *   TOKEN=...                      Meta system user token (never printed)
 *   WHATSAPP_BUSINESS_WABA_ID=...
 *
 * Flow published → back to Draft; must be re-published after every JSON upload.
 * If Meta refuses to update a published flow (returns an error on the asset
 * upload), the script automatically falls back to cloning the existing flow
 * via clone_flow_id= and publishes the clone instead.
 *
 * On success prints the flow ID — add/update in .env as WHATSAPP_PARENT_LINK_FLOW_ID.
 */
declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

// ------------------------------------------------------------------ //
// Config
// ------------------------------------------------------------------ //

$dryRun = in_array('--dry-run', $argv ?? [], true);

$token     = getenv('TOKEN') ?: config('services.whatsapp.business_api_token');
$wabaId    = getenv('WHATSAPP_BUSINESS_WABA_ID') ?: config('services.whatsapp.business_waba_id');
$apiVersion = config('services.whatsapp.business_api_version', 'v21.0');
$flowName   = 'parent_link_request';
$flowJsonPath = resource_path('whatsapp/flows/parent-link-request.json');

if ($dryRun) {
    echo "[DRY RUN] No Meta API writes will be made.\n\n";
}

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

// Validate JSON locally before touching Meta.
json_decode($flowJson, true, 512, JSON_THROW_ON_ERROR);
echo "Flow JSON parsed OK locally (fields: "
    .implode(', ', array_column(
        data_get(json_decode($flowJson, true), 'screens.0.layout.children.0.children', []),
        'name'
    )).")\n";

$base = "https://graph.facebook.com/{$apiVersion}";

// ------------------------------------------------------------------ //
// List existing flows
// ------------------------------------------------------------------ //

/** @var \Illuminate\Http\Client\Response $listResp */
$listResp = Http::withToken($token)->get("{$base}/{$wabaId}/flows", [
    'fields' => 'id,name,status',
    'limit'  => 50,
]);

if (! $listResp->successful()) {
    fwrite(STDERR, "Failed to list flows: HTTP {$listResp->status()}\n{$listResp->body()}\n");
    exit(1);
}

$allFlows = $listResp->json('data') ?? [];
echo "\nExisting flows on WABA {$wabaId}:\n";
foreach ($allFlows as $f) {
    echo "  [{$f['status']}] {$f['id']} — {$f['name']}\n";
}

$existingId     = null;
$existingStatus = null;
foreach ($allFlows as $flow) {
    if (($flow['name'] ?? '') === $flowName) {
        $existingId     = $flow['id'] ?? null;
        $existingStatus = $flow['status'] ?? null;
        break;
    }
}

echo "\n";

if ($existingId) {
    echo "Found existing flow {$existingId} ({$flowName}) — status: {$existingStatus}\n";
} else {
    echo "No existing flow named '{$flowName}' — would create a new one\n";
}

if ($dryRun) {
    echo "\n[DRY RUN] Done. Re-run without --dry-run to upload and publish.\n";
    exit(0);
}

// ------------------------------------------------------------------ //
// Obtain a writable flow ID (create or reuse existing)
// ------------------------------------------------------------------ //

if ($existingId) {
    $flowId = $existingId;
    echo "Reusing flow {$flowId}\n";
} else {
    $createResp = Http::withToken($token)->post("{$base}/{$wabaId}/flows", [
        'name'       => $flowName,
        'categories' => ['OTHER'],
    ]);

    if (! $createResp->successful()) {
        fwrite(STDERR, "Failed to create flow: HTTP {$createResp->status()}\n{$createResp->body()}\n");
        exit(1);
    }

    $flowId = $createResp->json('id');
    echo "Created flow {$flowId}\n";
}

// ------------------------------------------------------------------ //
// Upload JSON — with clone_flow_id fallback
// ------------------------------------------------------------------ //

$flowId = uploadJsonOrClone($token, $base, $wabaId, $flowId, $flowJson);

// ------------------------------------------------------------------ //
// Validate on Meta
// ------------------------------------------------------------------ //

$validateResp = Http::withToken($token)->post("{$base}/{$flowId}/validate");
if ($validateResp->successful()) {
    $validation = $validateResp->json();
    if (! empty($validation['validation_errors'])) {
        fwrite(STDERR, "Flow validation errors:\n".json_encode($validation, JSON_PRETTY_PRINT)."\n");
        exit(1);
    }
    echo "Flow JSON validated OK on Meta\n";
} else {
    echo "Validate endpoint returned HTTP {$validateResp->status()} — continuing to publish\n";
}

// ------------------------------------------------------------------ //
// Publish
// ------------------------------------------------------------------ //

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
echo "Add/update in .env:\nWHATSAPP_PARENT_LINK_FLOW_ID={$flowId}\n";

// ------------------------------------------------------------------ //
// Helper: upload JSON; on failure clone and retry
// ------------------------------------------------------------------ //

/**
 * Upload the Flow JSON asset to $flowId.  If Meta rejects the upload
 * (e.g. the flow is Published and cannot be edited in place), clone the
 * flow via clone_flow_id=, upload to the clone, and return the clone's ID.
 *
 * Token is never echoed or logged.
 */
function uploadJsonOrClone(
    string $token,
    string $base,
    string $wabaId,
    string $flowId,
    string $flowJson,
): string {
    $assetResp = Http::withToken($token)
        ->attach('file', $flowJson, 'flow.json')
        ->post("{$base}/{$flowId}/assets", [
            'name'       => 'flow.json',
            'asset_type' => 'FLOW_JSON',
        ]);

    if ($assetResp->successful()) {
        echo "Uploaded flow JSON to {$flowId}\n";

        return $flowId;
    }

    // Meta refused — fall back to clone.
    echo "Asset upload to {$flowId} failed (HTTP {$assetResp->status()}): {$assetResp->body()}\n";
    echo "Falling back to clone_flow_id…\n";

    $cloneResp = Http::withToken($token)->post("{$base}/{$wabaId}/flows", [
        'clone_flow_id' => $flowId,
        'name'          => 'parent_link_request_'.date('Ymd_Hi'),
    ]);

    if (! $cloneResp->successful()) {
        fwrite(STDERR, "Failed to clone flow {$flowId}: HTTP {$cloneResp->status()}\n{$cloneResp->body()}\n");
        exit(1);
    }

    $cloneId = $cloneResp->json('id');
    echo "Cloned {$flowId} → new flow {$cloneId}\n";

    $cloneAssetResp = Http::withToken($token)
        ->attach('file', $flowJson, 'flow.json')
        ->post("{$base}/{$cloneId}/assets", [
            'name'       => 'flow.json',
            'asset_type' => 'FLOW_JSON',
        ]);

    if (! $cloneAssetResp->successful()) {
        fwrite(STDERR, "Failed to upload JSON to clone {$cloneId}: HTTP {$cloneAssetResp->status()}\n{$cloneAssetResp->body()}\n");
        exit(1);
    }

    echo "Uploaded flow JSON to clone {$cloneId}\n";

    return $cloneId;
}
