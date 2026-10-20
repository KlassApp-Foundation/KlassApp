<?php

namespace App\Services\ConnectedTools;

use App\Contracts\ConnectedTools\ConnectedTool;
use App\Models\User;
use App\Services\Google\GoogleConnectorCatalog;
use DateTimeInterface;

/**
 * One list of connector cards for the signed-in user.
 *
 * A later pull request flips a Coming soon card by calling register() with a
 * ConnectedTool for that key. An explicit registration wins over the config
 * provider class and over the Google catalog fallback.
 */
class ConnectedToolsRegistry
{
    public const CONNECTED = 'connected';

    public const NOT_CONNECTED = 'not_connected';

    public const NEEDS_ATTENTION = 'needs_attention';

    public const COMING_SOON = 'coming_soon';

    public const ACTION_CONNECT = 'connect';

    public const ACTION_SETTINGS = 'settings';

    public const ACTION_DISCONNECT = 'disconnect';

    /** @var array<string, ConnectedTool> */
    private array $registered = [];

    public function __construct(private readonly GoogleConnectorCatalog $catalog)
    {
    }

    public function register(ConnectedTool $tool): void
    {
        $this->registered[$tool->key()] = $tool;
    }

    /**
     * @return list<array{
     *     key: string,
     *     label: string,
     *     summary: string,
     *     icon: string,
     *     status: string,
     *     status_label: string,
     *     attention: ?string,
     *     last_synced_at: ?DateTimeInterface,
     *     action: ?string,
     *     action_label: ?string,
     *     action_url: ?string,
     *     action_method: string
     * }>
     */
    public function cardsFor(User $user): array
    {
        $cards = [];

        foreach (config('connected_tools.tools', []) as $key => $meta) {
            $provider = $this->providerFor((string) $key, is_array($meta) ? $meta : []);
            $cards[] = $this->card((string) $key, is_array($meta) ? $meta : [], $provider, $user);
        }

        return $cards;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function providerFor(string $key, array $meta): ?ConnectedTool
    {
        if (isset($this->registered[$key])) {
            return $this->registered[$key];
        }

        $class = $meta['provider'] ?? null;

        if (is_string($class) && $class !== '' && class_exists($class)) {
            $made = app($class);

            if ($made instanceof ConnectedTool && $made->key() === $key) {
                return $made;
            }
        }

        if ($this->catalog->has($key)) {
            return new GoogleOAuthConnectedTool($key, $this->catalog);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    private function card(string $key, array $meta, ?ConnectedTool $provider, User $user): array
    {
        $base = [
            'key' => $key,
            'label' => (string) ($meta['label'] ?? $key),
            'summary' => (string) ($meta['summary'] ?? ''),
            'icon' => (string) ($meta['icon'] ?? ''),
            'attention' => null,
            'note' => null,
            'account_email' => null,
            'last_synced_at' => null,
            'action' => null,
            'action_label' => null,
            'action_url' => null,
            'action_method' => 'GET',
        ];

        if ($provider === null) {
            return array_merge($base, [
                'status' => self::COMING_SOON,
                'status_label' => 'Coming soon',
            ]);
        }

        $state = $provider->stateFor($user);
        $status = (string) ($state['status'] ?? self::NOT_CONNECTED);

        if (! in_array($status, [self::CONNECTED, self::NOT_CONNECTED, self::NEEDS_ATTENTION], true)) {
            $status = self::NOT_CONNECTED;
        }

        $action = $state['action'] ?? null;
        $labels = [
            self::ACTION_CONNECT => 'Connect',
            self::ACTION_SETTINGS => 'Settings',
            self::ACTION_DISCONNECT => 'Disconnect',
        ];

        return array_merge($base, [
            'status' => $status,
            'status_label' => match ($status) {
                self::CONNECTED => 'Connected',
                self::NEEDS_ATTENTION => 'Needs attention',
                default => 'Not connected',
            },
            'attention' => $status === self::NEEDS_ATTENTION ? ($state['attention'] ?? null) : null,
            'note' => $state['note'] ?? null,
            'account_email' => $status === self::CONNECTED ? ($state['account_email'] ?? null) : null,
            'last_synced_at' => $status === self::CONNECTED ? ($state['last_synced_at'] ?? null) : null,
            'action' => is_string($action) && isset($labels[$action]) ? $action : null,
            'action_label' => is_string($action) ? ($labels[$action] ?? null) : null,
            'action_url' => $state['action_url'] ?? null,
            'action_method' => strtoupper((string) ($state['action_method'] ?? 'GET')) === 'POST' ? 'POST' : 'GET',
        ]);
    }
}
