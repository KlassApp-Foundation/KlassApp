<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PublicFormSubmission;
use Illuminate\Http\Request;

class PublicFormSubmissionController extends Controller
{
    /**
     * Store a public form submission payload.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $payload = $request->except(['_token']);

        if (empty($payload)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No form data was provided.',
            ], 422);
        }

        $submission = PublicFormSubmission::create([
            'form_name' => 'saidi-shema-content-form',
            'source_url' => (string) $request->headers->get('origin', ''),
            'ip_address' => (string) $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'payload' => $payload,
        ]);

        return response()->json([
            'status' => 'ok',
            'message' => 'Submission received.',
            'id' => $submission->id,
        ], 201);
    }
}
