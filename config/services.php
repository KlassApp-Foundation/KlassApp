<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => env('SES_REGION', 'us-east-1'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\Models\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],
     'twilio' => [
         'sid' => env('TWILIO_SID'),
         'token' => env('TWILIO_TOKEN'),
         'key' => env('TWILIO_KEY'),
         'secret' => env('TWILIO_SECRET')
      ],

    'whatsapp' => [
        'business_number' => env('WHATSAPP_BUSINESS_NUMBER', '+256793844906'),
        'business_name' => env('WHATSAPP_BUSINESS_NAME', 'KlassApp'),

        // WhatsApp Business Cloud API (only transport)
        'business_api_token' => env('WHATSAPP_BUSINESS_API_TOKEN'),
        'business_phone_number_id' => env('WHATSAPP_BUSINESS_PHONE_NUMBER_ID'),
        'business_waba_id' => env('WHATSAPP_BUSINESS_WABA_ID'),
        'business_verify_token' => env('WHATSAPP_BUSINESS_VERIFY_TOKEN', 'klassapp_verify_2026'),
        'business_api_version' => env('WHATSAPP_BUSINESS_API_VERSION', 'v21.0'),
        'template_language' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'en'),
        'parent_link_flow_id' => env('WHATSAPP_PARENT_LINK_FLOW_ID'),
        'parent_link_flow_screen' => env('WHATSAPP_PARENT_LINK_FLOW_SCREEN', 'LINK_REQUEST'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', 'http://localhost:8000/auth/google/callback'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Slack MCP client (spike / plumbing — mock by default)
    |--------------------------------------------------------------------------
    | mode=mock  → local SpikeSlackMockServer via Mcp::local + Client::local
    | mode=live  → https://mcp.slack.com/mcp (needs credentials)
    | MCP Approvable/HITL for write tools is deferred — read audit via ToolInvoked.
    */
    'slack_mcp' => [
        'mode' => env('SLACK_MCP_MODE', 'mock'),
        'url' => env('SLACK_MCP_URL', 'https://mcp.slack.com/mcp'),
        'client_id' => env('SLACK_MCP_CLIENT_ID'),
        'client_secret' => env('SLACK_MCP_CLIENT_SECRET'),
        'token' => env('SLACK_MCP_TOKEN'),
        'timeout' => env('SLACK_MCP_TIMEOUT', 30),
    ],

];
