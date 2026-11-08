<?php

namespace Tests\Support;

use App\Models\SchoolGoogleConnectorAllow;
use App\Models\User;
use Illuminate\Support\Facades\Http;

trait InteractsWithFakeGoogle
{
    protected FakeGoogleServer $google;

    protected function setUpFakeGoogle(): void
    {
        $this->google = new FakeGoogleServer();
        $this->google->start();

        config([
            'services.google.client_id' => FakeGoogleServer::CLIENT_ID,
            'services.google.client_secret' => FakeGoogleServer::CLIENT_SECRET,
            'services.google.connector_redirect' => 'http://localhost/admin/settings/google/callback',
            'services.google.authorize_url' => $this->google->url('/o/oauth2/v2/auth'),
            'services.google.token_url' => $this->google->url('/token'),
            'services.google.revoke_url' => $this->google->url('/revoke'),
            'services.google.userinfo_url' => $this->google->url('/oauth2/v2/userinfo'),
            'services.google.sheets_base_url' => $this->google->baseUrl(),
            'services.google.drive_base_url' => $this->google->baseUrl(),
            'services.google.classroom_base_url' => $this->google->baseUrl(),
            'services.google.calendar_base_url' => $this->google->baseUrl(),
            'services.google.gmail_base_url' => $this->google->baseUrl(),
            'services.google.docs_base_url' => $this->google->baseUrl(),
            'services.google.slides_base_url' => $this->google->baseUrl(),
            'services.google.tasks_base_url' => $this->google->baseUrl(),
        ]);

        Http::preventStrayRequests();
        Http::allowStrayRequests([$this->google->baseUrl().'/*']);
    }

    protected function tearDownFakeGoogle(): void
    {
        if (isset($this->google)) {
            $this->google->stop();
        }
    }

    protected function connectGoogle(User $admin, string $connector): void
    {
        SchoolGoogleConnectorAllow::query()->updateOrCreate(
            ['school_id' => $admin->school_id, 'connector' => $connector],
            ['allowed' => true],
        );

        $response = $this->actingAs($admin)->get(route('admin.google.connect', ['connector' => $connector]));
        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertStringStartsWith($this->google->baseUrl(), $location);
        $this->assertStringNotContainsString(FakeGoogleServer::CLIENT_SECRET, $location);

        $issued = Http::withOptions(['allow_redirects' => false])
            ->withHeaders(['Accept' => 'application/json'])
            ->get($location);
        if (! $issued->successful()) {
            throw new \RuntimeException('Fake Google authorize failed: '.$issued->status().' '.$issued->body());
        }

        $this->actingAs($admin)->get('/admin/settings/google/callback?'.http_build_query([
            'code' => $issued->json('code'),
            'state' => $issued->json('state'),
        ]))->assertRedirect(route('google.mine'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function authorizeQuery(string $location): array
    {
        $query = [];
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        return $query;
    }
}
