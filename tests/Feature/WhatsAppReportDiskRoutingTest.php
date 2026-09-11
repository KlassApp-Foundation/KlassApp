<?php

namespace Tests\Feature;

use App\Services\WhatsAppReportCardDeliveryService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WhatsAppReportDiskRoutingTest extends TestCase
{
    public function test_report_disk_follows_configured_default(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);

        $delivery = app(WhatsAppReportCardDeliveryService::class);
        $token = 'abcdefghijklmnopqrstuvwxyz0123456789ABCD';
        $relative = WhatsAppReportCardDeliveryService::STORAGE_DIR.'/'.$token.'.pdf';

        $delivery->reportDisk()->put($relative, '%PDF-1.4 test');

        Storage::disk('local')->assertExists($relative);
        $this->assertSame($relative, $delivery->relativePathForToken($token));
        $this->assertNotNull($delivery->absolutePathForToken($token));

        $this->get(url('/whatsapp/report-files/'.$token))
            ->assertForbidden();
    }

    public function test_signed_route_streams_from_default_disk_without_local_path(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);

        $token = 'abcdefghijklmnopqrstuvwxyz0123456789ABCD';
        $relative = WhatsAppReportCardDeliveryService::STORAGE_DIR.'/'.$token.'.pdf';
        Storage::disk('local')->put($relative, '%PDF-1.4 streamed');

        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'whatsapp.report-file',
            now()->addMinutes(5),
            ['token' => $token]
        );

        $this->get($url)
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }
}
