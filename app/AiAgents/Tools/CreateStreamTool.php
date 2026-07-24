<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\AiAgents\Concerns\VerifiableTool;
use App\Helpers\SiteHelper;
use App\Models\AcademicYear;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Services\ToshiActionService;

/**
 * Creates a class (Section) and wires it into the current academic year via
 * StandardLink rows — either as a direct (no-stream) class or stream-first
 * (class created FROM its named streams, combined).
 *
 * ── Guard logic ──
 * Direct-class-on-top-of-streamed-class → BLOCKED.
 * Streaming a previously-direct class → ALLOWED (AssignStudentsToStreamTool
 * exists to actually move the stranded roster afterward). If
 * AssignStudentsToStreamTool is ever removed, this guard must revert to a
 * hard block.
 *
 * ── Terminology (Ugandan schools) ──
 *   Standard  → Phase (primary, secondary, nursery)
 *   Section   → Class (Primary 1, Senior 2)
 *   Stream    → Section split (East, West, A, B, Science)
 *   StandardLink → Junction table: phase × class × year, optionally × stream
 *
 * "Class" and "stream" are used in user-facing copy — never "standard."
 *
 * ── Multi-phase schools ──
 * If the school has more than one phase (e.g. both primary and secondary),
 * the `phase` parameter is required to disambiguate which Standard the
 * new class belongs to.
 */
class CreateStreamTool implements Tool, VerifiableTool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): string
    {
        return "Create a new class in the school's academic year, optionally splitting it into streams. For example: 'Create Primary 3 with streams East and West'. Existing classes can be streamed after creation — students can then be sorted into streams using the sort tool.";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'className' => $schema->string()->description('Class name, e.g. "Primary 1" or "Senior 2".'),
            'streams' => $schema->array()
                ->items($schema->string())
                ->description('Optional stream names, e.g. ["East", "West"] or ["A", "B"]. If omitted, the class is created without streams.'),
            'phase' => $schema->string()->description('Required if the school has more than one phase (e.g. "primary" vs "secondary"). Omit for single-phase schools.')->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeOrMessage($user);
        if ($error) return $error;

        $schoolId = ToshiActionService::getEffectiveSchoolId($user);
        if (!$schoolId) {
            return '❌ You are not assigned to a school.';
        }

        $className = trim((string) $request->get('className'));
        $streams = $this->normalizeNames($request->get('streams'));
        $phase = trim((string) $request->get('phase', ''));

        if ($className === '') {
            return '❌ Please provide a class name.';
        }

        // Resolve the Standard (phase) row
        $standard = $this->resolveStandard($schoolId, $phase);
        if ($standard === null) {
            return $phase !== ''
                ? "❌ Phase \"{$phase}\" doesn't exist in this school."
                : '❌ This school has multiple phases — please specify which phase the class belongs to (e.g. primary, secondary, nursery).';
        }

        // Find or create the Section (class)
        $section = Section::firstOrCreate(
            ['school_id' => $schoolId, 'name' => $className],
            ['status' => 1]
        );

        // Get the current academic year
        $academicYear = SiteHelper::getAcademicYear($schoolId);
        if (!$academicYear) {
            return '❌ No academic year configured for this school.';
        }
        $academicYearId = $academicYear->id;
        if ($academicYear instanceof AcademicYear) {
            $academicYearId = $academicYear->id;
        }

        // ── Guard: already-streamed class cannot be turned direct ──
        if (empty($streams)) {
            $existingStreamed = StandardLink::where('school_id', $schoolId)
                ->where('section_id', $section->id)
                ->where('academic_year_id', $academicYearId)
                ->whereNotNull('stream')
                ->where('stream', '!=', '')
                ->exists();

            if ($existingStreamed) {
                return "❌ \"{$className}\" already has streams. You cannot remove streams through this tool. Use the sort tool to move students between existing streams.";
            }
        }

        // ── Guard: if streams are given and a no-stream StandardLink exists,
        //    ALLOW it (mixed-mode guard is relaxed because
        //    AssignStudentsToStreamTool exists to move students off the old
        //    link). ──
        //    (No explicit block needed — we just create the new stream links.)

        $createdStreams = [];
        $batchArgs = ['className' => $className, 'streams' => $streams, 'phase' => $phase ?: null];

        if (empty($streams)) {
            // Direct class — one StandardLink with stream = null
            $existingLink = StandardLink::where('school_id', $schoolId)
                ->where('section_id', $section->id)
                ->where('academic_year_id', $academicYearId)
                ->whereNull('stream')
                ->first();

            if ($existingLink) {
                return "✅ \"{$className}\" already exists. No changes needed.";
            }

            $confirm = $this->confirmOrExecute('toolCreateStream', $batchArgs,
                fn() => "Create class: {$className} (no streams).");
            if ($confirm !== null) return $confirm;

            StandardLink::create([
                'school_id' => $schoolId,
                'academic_year_id' => $academicYearId,
                'standard_id' => $standard->id,
                'section_id' => $section->id,
                'stream' => null,
                'no_of_students' => 0,
                'status' => 1,
            ]);

            return "✅ Class **{$className}** created successfully.";
        }

        // Stream-first creation — one StandardLink per stream name
        $confirm = $this->confirmOrExecute('toolCreateStream', $batchArgs,
            fn() => "Create class: {$className} with streams: " . implode(', ', $streams) . ".");
        if ($confirm !== null) return $confirm;

        foreach ($streams as $streamName) {
            $existingStreamLink = StandardLink::where('school_id', $schoolId)
                ->where('section_id', $section->id)
                ->where('academic_year_id', $academicYearId)
                ->where('stream', $streamName)
                ->first();

            if ($existingStreamLink) {
                $createdStreams[] = "{$streamName} (already exists)";
                continue;
            }

            StandardLink::create([
                'school_id' => $schoolId,
                'academic_year_id' => $academicYearId,
                'standard_id' => $standard->id,
                'section_id' => $section->id,
                'stream' => $streamName,
                'no_of_students' => 0,
                'status' => 1,
            ]);

            $createdStreams[] = $streamName;
        }

        return "✅ Class **{$className}** created with " . count($createdStreams) . " stream(s): " . implode(', ', $createdStreams) . ".";
    }

    public function verify(Request $request): array
    {
        $user = auth()->user() ?? request()->user();
        $schoolId = ToshiActionService::getEffectiveSchoolId($user);
        if (!$schoolId) {
            return ['verified' => false, 'message' => 'No school ID for verification.'];
        }

        $className = trim((string) $request->get('className'));
        $streams = $this->normalizeNames($request->get('streams'));

        $section = Section::where('school_id', $schoolId)->where('name', $className)->first();
        if (!$section) {
            return ['verified' => false, 'message' => "Class \"{$className}\" not found in sections."];
        }

        $academicYear = SiteHelper::getAcademicYear($schoolId);
        if (!$academicYear) {
            return ['verified' => false, 'message' => 'No academic year for verification.'];
        }
        $academicYearId = $academicYear->id;
        if ($academicYear instanceof AcademicYear) {
            $academicYearId = $academicYear->id;
        }

        if (empty($streams)) {
            $link = StandardLink::where('school_id', $schoolId)
                ->where('section_id', $section->id)
                ->where('academic_year_id', $academicYearId)
                ->whereNull('stream')
                ->first();

            return [
                'verified' => $link !== null,
                'message' => $link
                    ? 'Direct class StandardLink confirmed in database.'
                    : 'Direct class StandardLink was not found after creation.',
            ];
        }

        $existing = StandardLink::where('school_id', $schoolId)
            ->where('section_id', $section->id)
            ->where('academic_year_id', $academicYearId)
            ->whereIn('stream', $streams)
            ->count();

        return [
            'verified' => $existing === count($streams),
            'message' => $existing === count($streams)
                ? 'All stream StandardLinks confirmed in database.'
                : "Only {$existing}/" . count($streams) . " stream links found.",
        ];
    }

    /**
     * Resolve the Standard (phase) row for this school.
     * Returns null if ambiguous (multi-phase school without a phase param)
     * or not found.
     */
    private function resolveStandard(int $schoolId, string $phase): ?Standard
    {
        $standards = Standard::where('school_id', $schoolId)
            ->where('status', 1)
            ->orderBy('order')
            ->get();

        if ($standards->isEmpty()) {
            return null;
        }

        if ($phase !== '') {
            $lowerPhase = strtolower($phase);
            return $standards->first(fn(Standard $s) => strtolower($s->name) === $lowerPhase)
                ?? $standards->first(fn(Standard $s) => str_contains($lowerPhase, strtolower($s->name)))
                ?? $standards->first(fn(Standard $s) => str_contains(strtolower($s->name), $lowerPhase));
        }

        // Single phase → auto-select
        if ($standards->count() === 1) {
            return $standards->first();
        }

        // Multi-phase but no phase given → ambiguous
        return null;
    }

    private function normalizeNames($raw): array
    {
        if (!is_array($raw)) return [];
        $names = array_map(fn($n) => trim((string) $n), $raw);
        $names = array_filter($names, fn($n) => $n !== '');
        return array_values(array_unique($names));
    }
}