<?php

namespace App\Support;

use App\Helpers\SiteHelper;
use App\Models\Admission;
use App\Models\Approval;
use App\Models\SchoolPayTransaction;
use App\Services\Marks\MarksSubmissionService;
use App\Services\WhatsApp\LinkRequestBoard;
use App\States\Approval\Pending;
use Illuminate\Support\Facades\Schema;

/**
 * Amber sidebar counts. Zero is omitted by the view. Each query is the
 * school's own rows and a positive status match.
 */
class SidebarCounts
{
    /** @return array{admissions: int, unmatched: int, approvals: int, whatsapp: int, mark_submissions: int} */
    public static function forSchool(?int $schoolId): array
    {
        $empty = ['admissions' => 0, 'unmatched' => 0, 'approvals' => 0, 'whatsapp' => 0, 'mark_submissions' => 0];
        if (! $schoolId) {
            return $empty;
        }

        // View-only tests render the menu before any migration. A missing table
        // means there is nothing to count, not a failed page.
        if (! Schema::hasTable('admissions')
            || ! Schema::hasTable('schoolpay_transactions')
            || ! Schema::hasTable('approvals')) {
            return $empty;
        }

        $yearId = Schema::hasTable('academic_years')
            ? SiteHelper::getAcademicYear($schoolId)?->id
            : null;
        $admissions = Admission::query()
            ->where('school_id', $schoolId)
            ->where('application_status', 'Pending')
            ->when($yearId, fn ($query) => $query->where('academic_year_id', $yearId))
            ->count();

        $unmatched = SchoolPayTransaction::query()
            ->where('school_id', $schoolId)
            ->whereNull('matched_fee_category_id')
            ->count();

        $approvals = Approval::query()
            ->where('state', Pending::class)
            ->whereHas('approvable', fn ($query) => $query->where('school_id', $schoolId))
            ->count();

        // WhatsApp link requests waiting for a decision (all three sources:
        // KlassApp number, form, demo BIND) — the same count the dashboard
        // stat and the footer bell use.
        $whatsapp = 0;
        if (Schema::hasTable('parent_link_requests')) {
            try {
                $whatsapp = (int) app(LinkRequestBoard::class)->pendingCount($schoolId);
            } catch (\Throwable $e) {
                $whatsapp = 0; // fail safe: a badge is not worth a broken page
            }
        }

        $markSubmissions = 0;
        if (Schema::hasTable('exam_marks_submissions')) {
            $markSubmissions = app(MarksSubmissionService::class)->pendingCount($schoolId);
        }

        return [
            'admissions' => $admissions,
            'unmatched' => $unmatched,
            'approvals' => $approvals,
            'whatsapp' => $whatsapp,
            'mark_submissions' => $markSubmissions,
        ];
    }
}
