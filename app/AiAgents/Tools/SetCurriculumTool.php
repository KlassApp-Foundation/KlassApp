<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\AiAgents\Concerns\VerifiableTool;
use App\Models\School;
use App\Services\ToshiActionService;

class SetCurriculumTool implements Tool, VerifiableTool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): string
    {
        return "Set the school's curriculum (e.g. UNEB, Cambridge, Montessori) and enable Toshi AI assistant. Call this as the very first onboarding step — before creating classes, subjects, or terms.";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'curriculum' => $schema->string()->enum(['uneb', 'cambridge', 'montessori', 'other'])->description('The curriculum or examination board the school follows. UNEB is the standard Ugandan curriculum.'),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        // Settings / academic-year curriculum config — school admin only, not deputies.
        $error = $this->authorizeSchoolAdminOrMessage($user);
        if ($error) return $error;

        $schoolId = ToshiActionService::getEffectiveSchoolId($user);
        if (!$schoolId) {
            return '❌ You are not assigned to a school.';
        }

        $curriculum = $request->get('curriculum');
        if (!$curriculum) {
            return '❌ Please specify a curriculum (uneb, cambridge, montessori, or other).';
        }

        $school = School::find($schoolId);
        if (!$school) {
            return '❌ School not found.';
        }

        $args = ['curriculum' => $curriculum];

        $confirm = $this->confirmOrExecute('toolSetCurriculum', $args,
            fn() => "Set curriculum to " . strtoupper($curriculum) . " and enable Toshi AI assistant");
        if ($confirm !== null) return $confirm;

        $school->curriculum = $curriculum;
        $school->toshi_enabled = true;
        $school->save();

        return "✅ Curriculum set to **" . strtoupper($curriculum) . "** and Toshi AI assistant is now enabled for this school.";
    }

    public function verify(Request $request): array
    {
        $user = auth()->user() ?? request()->user();
        $schoolId = ToshiActionService::getEffectiveSchoolId($user);
        if (!$schoolId) {
            return ['verified' => false, 'message' => 'No school ID for verification.'];
        }

        $school = School::find($schoolId);
        if (!$school) {
            return ['verified' => false, 'message' => 'School not found.'];
        }

        return [
            'verified' => $school->curriculum === $request->get('curriculum') && $school->toshi_enabled,
            'message' => $school->curriculum === $request->get('curriculum') && $school->toshi_enabled
                ? 'Curriculum and Toshi status confirmed in database.'
                : 'Curriculum or Toshi status does not match.',
        ];
    }
}
