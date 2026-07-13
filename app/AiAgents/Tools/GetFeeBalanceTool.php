<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\Models\FeePayment;
use App\Models\StudentAcademic;

class GetFeeBalanceTool implements Tool
{
    public function description(): string
    {
        return 'Get the current fee balance for a student. Provide the student ID.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'studentId' => $schema->integer()->description('Student user ID'),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $studentId = $request->get('studentId');
        $schoolId = $user->school_id;

        if (!$schoolId) {
            return 'You are not assigned to a school.';
        }

        $totalPaid = FeePayment::where('school_id', $schoolId)
            ->where('student_id', $studentId)
            ->sum('amount');

        $academic = StudentAcademic::where('school_id', $schoolId)
            ->where('user_id', $studentId)
            ->first();

        $balance = $academic ? ($academic->total_fee ?? 0) - $totalPaid : 0;
        $balance = max(0, $balance);

        return 'Fee balance for student ID ' . $studentId . ': **UGX ' . number_format($balance) . '** (Total paid: UGX ' . number_format($totalPaid) . ').';
    }
}
