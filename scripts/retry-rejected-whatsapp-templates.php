<?php

/**
 * Delete-by-name + resubmit the two templates rejected for INCORRECT_CATEGORY.
 */
declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = config('services.whatsapp.business_api_token');
$waba = config('services.whatsapp.business_waba_id');
$v = config('services.whatsapp.business_api_version', 'v21.0');
$lang = config('services.whatsapp.template_language', 'en');
$base = "https://graph.facebook.com/{$v}";

if (empty($token) || empty($waba)) {
    fwrite(STDERR, "Missing WhatsApp credentials\n");
    exit(1);
}

foreach (['teacher_account_invite', 'toshi_otp'] as $name) {
    $del = Http::withToken($token)->delete("{$base}/{$waba}/message_templates", [
        'name' => $name,
    ]);
    echo "DELETE {$name} HTTP {$del->status()}: ".json_encode($del->json()).PHP_EOL;
}

$payloads = [
    [
        'name' => 'teacher_account_invite',
        'language' => $lang,
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
    [
        'name' => 'toshi_otp',
        'language' => $lang,
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

foreach ($payloads as $payload) {
    $resp = Http::withToken($token)
        ->withHeaders(['Content-Type' => 'application/json'])
        ->post("{$base}/{$waba}/message_templates", $payload);
    echo "POST {$payload['name']} [{$payload['category']}] HTTP {$resp->status()}: ".json_encode($resp->json()).PHP_EOL;
}

$list = Http::withToken($token)->get("{$base}/{$waba}/message_templates", [
    'fields' => 'id,name,status,category,language,rejected_reason',
    'limit' => 100,
]);

$want = [
    'fee_update',
    'grade_entered',
    'report_card_ready',
    'health_incident',
    'teacher_account_invite',
    'toshi_otp',
];
$out = [];
foreach ($list->json('data') ?? [] as $row) {
    if (in_array($row['name'] ?? '', $want, true)) {
        $out[$row['name']] = $row;
    }
}

echo "LIVE:\n".json_encode($out, JSON_PRETTY_PRINT).PHP_EOL;
