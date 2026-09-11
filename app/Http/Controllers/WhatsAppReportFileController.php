<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppReportCardDeliveryService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WhatsAppReportFileController extends Controller
{
    /**
     * Public GET for Meta's document.link fetch. Auth is the signed query string.
     */
    public function show(string $token, WhatsAppReportCardDeliveryService $delivery): BinaryFileResponse|StreamedResponse
    {
        $path = $delivery->absolutePathForToken($token);
        if ($path !== null) {
            return response()->file($path, [
                'Content-Type' => 'application/pdf',
                'Cache-Control' => 'private, max-age=0, no-store',
            ]);
        }

        $relative = $delivery->relativePathForToken($token);
        if ($relative === null) {
            abort(404);
        }

        return $delivery->reportDisk()->response($relative, null, [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, max-age=0, no-store',
        ]);
    }
}
