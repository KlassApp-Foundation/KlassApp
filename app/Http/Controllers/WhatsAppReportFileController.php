<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppReportCardDeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WhatsAppReportFileController extends Controller
{
    /**
     * Public GET for Meta's document.link fetch. Auth is the signed query string.
     *
     * Any invalid, tampered or expired link - and any link whose file is gone -
     * renders a plain-language "link expired" page instead of a raw signature error.
     */
    public function show(Request $request, string $token, WhatsAppReportCardDeliveryService $delivery): BinaryFileResponse|StreamedResponse|\Illuminate\Http\Response
    {
        if (! $request->hasValidSignature()) {
            Log::warning('Report file link rejected: invalid or expired signature.');

            return response()->view('errors.report-link-expired', [], 410);
        }

        $path = $delivery->absolutePathForToken($token);
        if ($path !== null) {
            return response()->file($path, [
                'Content-Type' => 'application/pdf',
                'Cache-Control' => 'private, max-age=0, no-store',
            ]);
        }

        $relative = $delivery->relativePathForToken($token);
        if ($relative === null) {
            return response()->view('errors.report-link-expired', [], 410);
        }

        return $delivery->reportDisk()->response($relative, null, [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, max-age=0, no-store',
        ]);
    }
}
