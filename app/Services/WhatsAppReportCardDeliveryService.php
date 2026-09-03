<?php

namespace App\Services;

use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Parent-initiated report-card PDF for WhatsApp.
 *
 * Uses the same StudentReportCardService pipeline as admin/teacher downloads
 * (not the stripped whatsapp.report-card view). Files live on the private
 * local disk and are fetched by Meta via a short-lived signed URL.
 */
class WhatsAppReportCardDeliveryService
{
    public const STORAGE_DIR = 'whatsapp-reports';

    public const SIGNED_TTL_MINUTES = 30;

    public const RATE_LIMIT_PER_HOUR = 5;

    public const META_DOCUMENT_MAX_BYTES = 100 * 1024 * 1024;

    public function __construct(private StudentReportCardService $reports) {}

    /**
     * @return array{ok: true, url: string, filename: string, caption: string, bytes: int}|array{ok: false, message: string, flow_type: string}
     */
    public function prepareForStudent(User $parent, User $student): array
    {
        $link = StudentParentLink::query()
            ->where('parent_id', $parent->id)
            ->where('student_id', $student->id)
            ->where('status', 1)
            ->whereNotNull('school_id')
            ->first();

        if (! $link) {
            return [
                'ok' => false,
                'flow_type' => 'report_not_linked',
                'message' => $this->emptyCopy(
                    $student,
                    "This student is not linked to your account.\n\nPlease contact your school office if you expected a report card here.",
                ),
            ];
        }

        $schoolId = (int) $link->school_id;

        // Fresh query — do not trust relation state on the in-memory User
        // (User::$with / earlier loads can leave studentAcademicLatest null).
        $stdLink = StudentAcademic::query()
            ->where('user_id', $student->id)
            ->whereIn('academic_year_id', function ($query) {
                $query->select('id')
                    ->from('academic_years')
                    ->where('status', 1);
            })
            ->with('standardLink')
            ->orderByDesc('id')
            ->first()
            ?->standardLink;

        if (! $stdLink) {
            return [
                'ok' => false,
                'flow_type' => 'report_no_class',
                'message' => $this->emptyCopy(
                    $student,
                    "No class is assigned yet, so a report card cannot be generated.\n\nPlease contact the school office.",
                ),
            ];
        }

        $exam = $this->reports->resolveExam($schoolId, $stdLink);
        if (! $exam) {
            return [
                'ok' => false,
                'flow_type' => 'report_none',
                'message' => $this->emptyCopy(
                    $student,
                    "No report card has been published yet.\n\nIt will be available here once the school releases end-of-term results.",
                ),
            ];
        }

        if (! $this->reports->studentIds($exam)->contains($student->id)) {
            return [
                'ok' => false,
                'flow_type' => 'report_none',
                'message' => $this->emptyCopy(
                    $student,
                    "No marks have been entered for this term yet.\n\nThe report card will appear here once the school publishes it.",
                ),
            ];
        }

        try {
            $pdf = $this->reports->pdfForStudent($schoolId, $stdLink, $student, null);
        } catch (RuntimeException $e) {
            return [
                'ok' => false,
                'flow_type' => 'report_none',
                'message' => $this->emptyCopy(
                    $student,
                    "No report card has been published yet.\n\nIt will be available here once the school releases end-of-term results.",
                ),
            ];
        } catch (Throwable $e) {
            Log::error('WhatsApp report card PDF failed', [
                'parent_id' => $parent->id,
                'student_id' => $student->id,
                'school_id' => $schoolId,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'flow_type' => 'report_error',
                'message' => $this->emptyCopy(
                    $student,
                    "We couldn't generate the report card just now. Please try again in a few minutes.",
                ),
            ];
        }

        $bytes = strlen($pdf);
        if ($bytes < 5 || ! str_starts_with($pdf, '%PDF') || $bytes > self::META_DOCUMENT_MAX_BYTES) {
            Log::error('WhatsApp report card PDF failed size/format check', [
                'student_id' => $student->id,
                'bytes' => $bytes,
            ]);

            return [
                'ok' => false,
                'flow_type' => 'report_error',
                'message' => $this->emptyCopy(
                    $student,
                    "We couldn't generate the report card just now. Please try again in a few minutes.",
                ),
            ];
        }

        $token = Str::random(40);
        $relative = self::STORAGE_DIR.'/'.$token.'.pdf';
        $disk = Storage::disk('local');
        if (! $disk->exists(self::STORAGE_DIR)) {
            $disk->makeDirectory(self::STORAGE_DIR);
        }
        // Ensure FPM (appuser) can read files even if an earlier root/tinker
        // process created the directory with restrictive ownership/mode.
        $dirPath = $disk->path(self::STORAGE_DIR);
        if (is_dir($dirPath)) {
            @chmod($dirPath, 0775);
        }
        $disk->put($relative, $pdf);
        @chmod($disk->path($relative), 0644);

        $name = $student->whatsappDisplayName('Student');
        $slug = Str::slug($name) ?: 'student';

        return [
            'ok' => true,
            'url' => URL::temporarySignedRoute(
                'whatsapp.report-file',
                now()->addMinutes(self::SIGNED_TTL_MINUTES),
                ['token' => $token],
            ),
            'filename' => $slug.'-report-card.pdf',
            'caption' => "📄 Report card — {$name}",
            'bytes' => $bytes,
        ];
    }

    public function pruneOlderThanHours(int $hours = 2): int
    {
        $disk = Storage::disk('local');
        if (! $disk->exists(self::STORAGE_DIR)) {
            return 0;
        }

        $cutoff = now()->subHours(max(1, $hours))->getTimestamp();
        $deleted = 0;

        foreach ($disk->files(self::STORAGE_DIR) as $file) {
            if (! str_ends_with($file, '.pdf')) {
                continue;
            }
            if ($disk->lastModified($file) < $cutoff) {
                $disk->delete($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    public function absolutePathForToken(string $token): ?string
    {
        if (! preg_match('/^[A-Za-z0-9]{40}$/', $token)) {
            return null;
        }

        $relative = self::STORAGE_DIR.'/'.$token.'.pdf';
        if (! Storage::disk('local')->exists($relative)) {
            return null;
        }

        return Storage::disk('local')->path($relative);
    }

    private function emptyCopy(User $student, string $body): string
    {
        $name = $student->whatsappDisplayName('your child');

        return "📄 *{$name}*\n\n{$body}";
    }
}
