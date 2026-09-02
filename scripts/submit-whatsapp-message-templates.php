<?php

/**
 * Submit KlassApp WhatsApp message templates to Meta Graph API.
 *
 * Usage (on a host with WhatsApp credentials configured):
 *   php scripts/submit-whatsapp-message-templates.php
 *   php scripts/submit-whatsapp-message-templates.php --list-only
 *
 * Reports real submission IDs and statuses — does not assume success.
 */
declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = getenv('TOKEN') ?: config('services.whatsapp.business_api_token');
$wabaId = getenv('WHATSAPP_BUSINESS_WABA_ID') ?: config('services.whatsapp.business_waba_id');
$apiVersion = config('services.whatsapp.business_api_version', 'v21.0');
$language = config('services.whatsapp.template_language', 'en');
$listOnly = in_array('--list-only', $argv, true);

if (empty($token) || empty($wabaId)) {
    fwrite(STDERR, "Error: WHATSAPP_BUSINESS_API_TOKEN and WHATSAPP_BUSINESS_WABA_ID are required.\n");
    exit(1);
}

$base = "https://graph.facebook.com/{$apiVersion}";

/**
 * @return array{data?: list<array<string, mixed>>, error?: mixed}
 */
function listTemplates(string $base, string $wabaId, string $token): array
{
    $resp = Http::withToken($token)->get("{$base}/{$wabaId}/message_templates", [
        'fields' => 'id,name,status,category,language,rejected_reason',
        'limit' => 100,
    ]);

    return [
        'http' => $resp->status(),
        'body' => $resp->json(),
        'ok' => $resp->successful(),
    ];
}

$list = listTemplates($base, $wabaId, $token);
echo "=== Existing templates (HTTP {$list['http']}) ===\n";
echo json_encode($list['body'], JSON_PRETTY_PRINT)."\n\n";

if ($listOnly) {
    exit($list['ok'] ? 0 : 1);
}

/** @var list<array<string, mixed>> $templates */
$templates = [
    [
        'name' => 'fee_update',
        'language' => $language,
        'category' => 'UTILITY',
        'components' => [
            [
                'type' => 'BODY',
                'text' => "Hello {{1}}, this is an update on {{2}}'s school fees. Balance: {{3}} — {{4}}. Contact {{5}} with any questions.",
                'example' => [
                    'body_text' => [[
                        'Jane Parent',
                        'Amina',
                        'UGX 150,000',
                        'due 15 Sep',
                        'the school office',
                    ]],
                ],
            ],
        ],
    ],
    [
        'name' => 'grade_entered',
        'language' => $language,
        'category' => 'UTILITY',
        'components' => [
            [
                'type' => 'BODY',
                'text' => 'Hello {{1}}, a new {{2}} grade for {{3}} has been recorded for {{4}} this term. Reply GRADES anytime to view the latest results on WhatsApp.',
                'example' => [
                    'body_text' => [[
                        'Jane Parent',
                        'Mathematics',
                        'Amina',
                        'Mid Term',
                    ]],
                ],
            ],
            [
                'type' => 'FOOTER',
                'text' => 'KlassApp — School Management',
            ],
        ],
    ],
    [
        'name' => 'report_card_ready',
        'language' => $language,
        'category' => 'UTILITY',
        'components' => [
            [
                'type' => 'BODY',
                'text' => "Hello {{1}}, {{2}}'s report card for {{3}} is now ready to view. We'll send the PDF in our next message.",
                'example' => [
                    'body_text' => [[
                        'Jane Parent',
                        'Amina',
                        'Term 1 2026',
                    ]],
                ],
            ],
        ],
    ],
    [
        'name' => 'health_incident',
        'language' => $language,
        'category' => 'UTILITY',
        'components' => [
            [
                'type' => 'HEADER',
                'format' => 'TEXT',
                'text' => 'Health Center Notice',
            ],
            [
                'type' => 'BODY',
                'text' => 'Hello {{1}}, {{2}} was attended to at the {{3}} school health center today regarding {{4}}. Please contact {{5}} for more details.',
                'example' => [
                    'body_text' => [[
                        'Jane Parent',
                        'Amina',
                        'Kabale Junior',
                        'mild fever',
                        'the school nurse',
                    ]],
                ],
            ],
        ],
    ],
    // Meta classified invite as non-utility (INCORRECT_CATEGORY) — MARKETING is the correct bucket.
    [
        'name' => 'teacher_account_invite',
        'language' => $language,
        'category' => 'MARKETING',
        'components' => [
            [
                'type' => 'BODY',
                'text' => "Hello {{1}}, you've been added as the class teacher for {{2}} at {{3}} on KlassApp. Your temporary login is {{4}}. Visit {{5}} to log in and set your password.",
                'example' => [
                    'body_text' => [[
                        'Mr Okello',
                        'P.3',
                        'Kabale Junior School',
                        'teacher@example.sch.ug',
                        'https://klassapp.xyz/login',
                    ]],
                ],
            ],
        ],
    ],
    // AUTHENTICATION cannot carry custom BODY text — Meta owns the OTP copy.
    // Custom wording was rejected as UTILITY (INCORRECT_CATEGORY → must be AUTH).
    [
        'name' => 'toshi_otp',
        'language' => $language,
        'category' => 'AUTHENTICATION',
        'components' => [
            [
                'type' => 'BODY',
                'add_security_recommendation' => true,
            ],
            [
                'type' => 'FOOTER',
                'code_expiration_minutes' => 5,
            ],
            [
                'type' => 'BUTTONS',
                'buttons' => [
                    [
                        'type' => 'OTP',
                        'otp_type' => 'COPY_CODE',
                    ],
                ],
            ],
        ],
    ],
];

$targetNames = array_column($templates, 'name');
$existingByName = [];
foreach ($list['body']['data'] ?? [] as $row) {
    $name = $row['name'] ?? '';
    if (in_array($name, $targetNames, true)) {
        $existingByName[$name] = $row;
    }
}

echo "=== Submitting templates ===\n";
$results = [];

foreach ($templates as $template) {
    $name = $template['name'];

    if (isset($existingByName[$name])) {
        $existing = $existingByName[$name];
        $status = $existing['status'] ?? 'UNKNOWN';
        echo "— {$name}: already exists id=".($existing['id'] ?? '?')." status={$status}\n";

        // Delete rejected/paused so we can resubmit with the same name.
        // Meta requires delete-by-name on the WABA node (delete-by-id returns #100/33).
        if (in_array($status, ['REJECTED', 'PAUSED', 'DISABLED'], true)) {
            $del = Http::withToken($token)->delete("{$base}/{$wabaId}/message_templates", [
                'name' => $name,
            ]);
            echo "  deleted prior {$status} template by name HTTP {$del->status()}: ".json_encode($del->json())."\n";
            if (! $del->successful()) {
                $results[$name] = [
                    'action' => 'delete_failed',
                    'http' => $del->status(),
                    'body' => $del->json(),
                    'prior' => $existing,
                ];
                continue;
            }
        } elseif (in_array($status, ['APPROVED', 'PENDING', 'IN_APPEAL'], true)) {
            $results[$name] = [
                'action' => 'skipped_existing',
                'id' => $existing['id'] ?? null,
                'status' => $status,
                'category' => $existing['category'] ?? null,
                'rejected_reason' => $existing['rejected_reason'] ?? null,
            ];
            continue;
        }
    }

    $resp = Http::withToken($token)
        ->withHeaders(['Content-Type' => 'application/json'])
        ->post("{$base}/{$wabaId}/message_templates", $template);

    $payload = $resp->json();
    echo "— {$name} [{$template['category']}] HTTP {$resp->status()}: ".json_encode($payload)."\n";

    $results[$name] = [
        'action' => $resp->successful() ? 'submitted' : 'failed',
        'category_used' => $template['category'],
        'http' => $resp->status(),
        'id' => $payload['id'] ?? null,
        'status' => $payload['status'] ?? null,
        'category' => $payload['category'] ?? $template['category'],
        'body' => $payload,
    ];
}

echo "\n=== Re-fetch statuses for the 6 targets ===\n";
$after = listTemplates($base, $wabaId, $token);
$final = [];
foreach ($after['body']['data'] ?? [] as $row) {
    $name = $row['name'] ?? '';
    if (in_array($name, $targetNames, true)) {
        $final[$name] = [
            'id' => $row['id'] ?? null,
            'status' => $row['status'] ?? null,
            'category' => $row['category'] ?? null,
            'language' => $row['language'] ?? null,
            'rejected_reason' => $row['rejected_reason'] ?? null,
        ];
    }
}

echo json_encode([
    'submit_results' => $results,
    'live_statuses' => $final,
], JSON_PRETTY_PRINT)."\n";

$failed = array_filter($results, fn ($r) => ($r['action'] ?? '') === 'failed');
exit($failed === [] ? 0 : 1);
