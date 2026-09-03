<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppReportCardDeliveryService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WhatsAppReportFileController extends Controller
{
    /**
     * Public GET for Meta's document.link fetch. Auth is the signed query string.
     */
    public function show(string $token, WhatsAppReportCardDeliveryService $delivery): BinaryFileResponse
    {
        $path = $delivery->absolutePathForToken($token);
        if ($path === null) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, max-age=0, no-store',
        ]);
    }
}
