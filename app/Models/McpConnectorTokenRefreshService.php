<?php

namespace App\Models;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class McpConnectorTokenRefreshService
{
    public function refresh(SchoolMcpConnector $connector): bool
    {
        $refreshToken = $connector->refreshToken();

        if ($refreshToken === null) {
            Log::warning('McpConnectorTokenRefresh: no refresh token', [
                'connector_id' => $connector->id,
                'type' => $connector->connector_type,
            ]);

            return false;
        }

        $catalog = config("toshi.mcp_connectors.{$connector->connector_type}");

        if ($catalog === null) {
            Log::error('McpConnectorTokenRefresh: unknown connector type', [
                'type' => $connector->connector_type,
            ]);

            return false;
        }

        try {
            $response = Http::asForm()->post($catalog['token_url'] ?? '', [
                'grant_type' => 'refresh_token',
                'client_id' => $catalog['oauth_client_id'],
                'client_secret' => $catalog['oauth_secret'] ?? null,
                'refresh_token' => $refreshToken,
            ]);

            if (! $response->successful()) {
                Log::error('McpConnectorTokenRefresh: refresh failed', [
                    'connector_id' => $connector->id,
                    'status' => $response->status(),
                ]);

                return false;
            }

            $data = $response->json();

            $credentials = $connector->credentials;
            $credentials['access_token'] = $data['access_token'];
            $credentials['token_type'] = $data['token_type'] ?? 'Bearer';

            if (isset($data['refresh_token'])) {
                $credentials['refresh_token'] = $data['refresh_token'];
            }

            $expiresIn = $data['expires_in'] ?? 3600;

            $connector->update([
                'credentials' => $credentials,
                'token_expires_at' => now()->addSeconds($expiresIn),
                'last_refreshed_at' => now(),
            ]);

            return true;
        } catch (RuntimeException $e) {
            Log::error('McpConnectorTokenRefresh: exception', [
                'connector_id' => $connector->id,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function sweepExpiringTokens(int $thresholdMinutes = 30): int
    {
        $threshold = now()->addMinutes($thresholdMinutes);

        $connectors = SchoolMcpConnector::query()
            ->active()
            ->whereNotNull('token_expires_at')
            ->where('token_expires_at', '<=', $threshold)
            ->get();

        $refreshed = 0;

        foreach ($connectors as $connector) {
            if ($this->refresh($connector)) {
                $refreshed++;
            }
        }

        return $refreshed;
    }
}
