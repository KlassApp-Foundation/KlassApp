<?php

namespace App\AiAgents\Tools;

use App\Services\ToshiActionService;
use App\Services\UserPreferenceService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Self-scoped get/set of the signed-in user's Toshi preferences.
 *
 * Intentionally does NOT use ConfirmsBeforeWrite so WhatsAppWriteExclusion
 * leaves it available on WhatsApp (no Tier-2). Never accepts a user_id —
 * targeting is always Auth::id() / getEffectiveUser().
 */
class ManagePreferencesTool implements Tool
{
    public function __construct(
        private readonly UserPreferenceService $preferences = new UserPreferenceService,
    ) {}

    public function description(): Stringable|string
    {
        return 'Get or update the signed-in user\'s own Toshi preferences '
            .'(language, notification channel, digest settings, timezone). '
            .'action=get|set. Never targets another user — no user_id parameter.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()->required(),
            'preferred_language' => $schema->string()->nullable(),
            'notification_channel' => $schema->string()->nullable(),
            'digest_enabled' => $schema->boolean()->nullable(),
            'digest_frequency' => $schema->string()->nullable(),
            'digest_weekday' => $schema->integer()->nullable(),
            'timezone' => $schema->string()->nullable(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $authUser = Auth::user() ?? request()->user();
        if (! $authUser) {
            return '❌ You must be signed in to manage preferences.';
        }

        $user = ToshiActionService::getEffectiveUser($authUser);

        $action = strtolower((string) ($request->get('action') ?? 'get'));

        if ($action === 'get') {
            $preference = $this->preferences->get($user);

            return '✅ Your preferences: '.$this->format($preference);
        }

        if ($action !== 'set' && $action !== 'update') {
            return '❌ action must be get or set.';
        }

        try {
            $preference = $this->preferences->set($user, [
                'preferred_language' => $request->get('preferred_language'),
                'notification_channel' => $request->get('notification_channel'),
                'digest_enabled' => $request->get('digest_enabled'),
                'digest_frequency' => $request->get('digest_frequency'),
                'digest_weekday' => $request->get('digest_weekday'),
                'timezone' => $request->get('timezone'),
            ]);
        } catch (\InvalidArgumentException $e) {
            return '❌ '.$e->getMessage();
        }

        return '✅ Preferences updated: '.$this->format($preference);
    }

    private function format(\App\Models\UserPreference $preference): string
    {
        $data = $this->preferences->toArray($preference);

        return json_encode($data, JSON_UNESCAPED_SLASHES) ?: '{}';
    }
}
