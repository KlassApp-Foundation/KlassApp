<?php

namespace App\Services\Google;

use App\Models\UserGoogleConnection;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GoogleApiClient
{
    public function __construct(private readonly GoogleOAuthService $oauth)
    {
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function get(UserGoogleConnection $connection, string $url, array $query = []): array
    {
        return $this->request($connection, 'GET', $url, ['query' => $query]);
    }

    /**
     * @param  array<string, mixed>  $body
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function postJson(UserGoogleConnection $connection, string $url, array $body = [], array $query = []): array
    {
        return $this->request($connection, 'POST', $url, ['json' => $body, 'query' => $query]);
    }

    /**
     * @param  array<string, mixed>  $body
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function putJson(UserGoogleConnection $connection, string $url, array $body = [], array $query = []): array
    {
        return $this->request($connection, 'PUT', $url, ['json' => $body, 'query' => $query]);
    }

    /**
     * Multipart upload (Drive). $parts is a list of Guzzle multipart elements.
     *
     * @param  list<array<string, mixed>>  $parts
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function postMultipart(UserGoogleConnection $connection, string $url, array $parts, array $query = []): array
    {
        return $this->request($connection, 'POST', $url, ['multipart' => $parts, 'query' => $query]);
    }

    /**
     * Raw body for Drive's multipart/related upload. Form-data is a different encoding.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function postBody(UserGoogleConnection $connection, string $url, string $body, string $contentType, array $query = []): array
    {
        return $this->request($connection, 'POST', $url, [
            'raw' => $body,
            'content_type' => $contentType,
            'query' => $query,
        ]);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function request(UserGoogleConnection $connection, string $method, string $url, array $options = []): array
    {
        $connection = $this->oauth->validConnection($connection);
        $response = $this->dispatch($connection, $method, $url, $options);

        if ($response->status() === 401) {
            $connection = $this->oauth->refresh($connection);
            $response = $this->dispatch($connection, $method, $url, $options);
        }

        if ($connection->exists) {
            $connection->forceFill(['last_used_at' => now()])->save();
        }

        if (! $response->successful()) {
            $body = $response->json();
            $error = is_array($body) ? ($body['error'] ?? null) : null;
            $message = 'Google API request failed.';
            $code = null;

            if (is_array($error)) {
                $message = (string) ($error['message'] ?? $message);
                $code = isset($error['status']) ? (string) $error['status'] : null;
            } elseif (is_string($error)) {
                $code = $error;
                $message = (string) (is_array($body) ? ($body['error_description'] ?? $message) : $message);
            }

            throw new GoogleApiException($response->status(), $message, $code);
        }

        return $response->json() ?? [];
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function dispatch(UserGoogleConnection $connection, string $method, string $url, array $options): Response
    {
        $pending = Http::withToken((string) $connection->accessToken())
            ->acceptJson()
            ->timeout(30);

        if (isset($options['query'])) {
            $pending = $pending->withQueryParameters($options['query']);
        }

        if (isset($options['multipart'])) {
            return $pending->send($method, $url, ['multipart' => $options['multipart']]);
        }

        if (isset($options['raw'])) {
            return $pending->withBody($options['raw'], (string) $options['content_type'])->send($method, $url);
        }

        if (array_key_exists('json', $options)) {
            return $pending->send($method, $url, ['json' => $options['json']]);
        }

        return $pending->send($method, $url);
    }
}
