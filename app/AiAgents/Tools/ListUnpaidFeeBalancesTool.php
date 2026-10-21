<?php

namespace App\AiAgents\Tools;

use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\Models\AcademicTerm;
use App\Services\FeePositionService;
use App\Services\Toshi\SchoolStreamLookup;
use App\Services\ToshiActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

/**
 * Students with an unpaid fee balance. Amounts come from FeePositionService,
 * the same calculation as the payments dashboard.
 */
class ListUnpaidFeeBalancesTool implements Tool
{
    use AuthorizesToshiAction;

    private const MAX_ROWS = 40;

    public function description(): string
    {
        return 'List students who still owe fees. Optional filters: class (section or standard name), stream, and term name. Balances use the same category-minus-payments figures as the payments dashboard. Read only.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'class' => $schema->string()->description('Optional class or section name, for example P.1')->nullable(),
            'stream' => $schema->string()->description('Optional stream name on that class')->nullable(),
            'term' => $schema->string()->description('Optional academic term name. When that term has its own fee categories, only those categories count.')->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeOrMessage($user);
        if ($error) {
            return $error;
        }

        $schoolId = ToshiActionService::getEffectiveSchoolId($user);
        if (! $schoolId) {
            return '❌ You are not assigned to a school.';
        }

        $class = $this->text($request->get('class'));
        $stream = $this->text($request->get('stream'));
        $termName = $this->text($request->get('term'));

        $filters = [];
        if ($class !== null || $stream !== null) {
            $links = SchoolStreamLookup::currentLinks((int) $schoolId, $class, $stream);
            if ($links->isEmpty()) {
                return 'No class matched that filter in your school.';
            }
            $filters['standard_link_ids'] = $links->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        if ($termName !== null) {
            $term = AcademicTerm::query()
                ->where('school_id', $schoolId)
                ->whereRaw('lower(name) = ?', [mb_strtolower($termName)])
                ->first();
            if (! $term) {
                return 'No term named "'.$termName.'" in your school.';
            }
            $filters['academic_term_id'] = (int) $term->id;
        }

        $rows = app(FeePositionService::class)->studentsWithBalance((int) $schoolId, $filters);
        if ($rows === []) {
            return 'No students with an unpaid fee balance for that filter.';
        }

        usort($rows, function (array $a, array $b) {
            $byBalance = $b['balance'] <=> $a['balance'];

            return $byBalance !== 0 ? $byBalance : strcasecmp($a['name'], $b['name']);
        });

        $total = count($rows);
        $shown = array_slice($rows, 0, self::MAX_ROWS);
        $lines = array_map(function (array $row) {
            $classLabel = $row['class_label'] !== '' ? $row['class_label'] : 'class not set';

            return '• '.$row['name'].' — '.$classLabel.' — UGX '.number_format((float) $row['balance']);
        }, $shown);

        $header = '**Unpaid fee balances ('.$total.'):**';
        if ($total > self::MAX_ROWS) {
            $header .= ' Showing the first '.self::MAX_ROWS.'.';
        }

        return $header."\n".implode("\n", $lines);
    }

    private function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
