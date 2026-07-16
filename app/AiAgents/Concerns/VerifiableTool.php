<?php

namespace App\AiAgents\Concerns;

use Laravel\Ai\Tools\Request;

interface VerifiableTool
{
    /**
     * Verify that the write actually happened by re-reading from the DB.
     *
     * Called immediately after handle() returns a non-error result.
     * Should return an associative array with at minimum ['verified' => bool]
     * and optionally ['record' => [...]] with the re-read record data.
     *
     * Returning ['verified' => false, 'message' => '...'] causes
     * executeConfirmedTool() to report uncertainty to the user instead of
     * false success. This is the same pattern the manual persistence
     * spot-checks were built to catch — now automated.
     */
    public function verify(Request $request): array;
}
