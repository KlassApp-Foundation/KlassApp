<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\FeePayment;
use App\Models\FeesCategories;
use App\Models\ParentProfile;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use Illuminate\Support\Carbon;

class ParentProfileService
{
    public function find(int $schoolId, string $name): User
    {
        $parent = User::query()
            ->where('school_id', $schoolId)
            ->where('usergroup_id', 7)
            ->where('name', $name)
            ->first();

        if ($parent === null && ctype_digit($name)) {
            $parent = User::query()
                ->where('id', (int) $name)
                ->where('school_id', $schoolId)
                ->where('usergroup_id', 7)
                ->first();
        }

        abort_unless($parent, 404);

        return $parent;
    }

    /**
     * @return array<string, mixed>
     */
    public function page(int $schoolId, User $parent): array
    {
        $profile = $parent->userprofile;
        $name = $this->personName($parent, $profile);
        $relation = ParentProfile::query()
            ->where('school_id', $schoolId)
            ->where('user_id', $parent->id)
            ->whereNotNull('relation')
            ->orderByDesc('id')
            ->value('relation');
        $relation = trim((string) $relation);
        $currency = app(DashboardV2DataService::class)->currencyFor($schoolId);
        $children = $this->children($schoolId, $parent, $currency);
        $fees = $this->feesTile($children, $currency);
        $whatsapp = WhatsAppUser::query()
            ->where('school_id', $schoolId)
            ->where('user_id', $parent->id)
            ->first();
        $optedIn = (bool) ($whatsapp?->opted_in);

        return [
            'parent' => $parent,
            'name' => $name,
            'initials' => $this->initials($profile?->firstname, $profile?->lastname, $name),
            'role' => $relation !== '' ? $this->titleLabel($relation) : 'Parent',
            'status' => (string) $parent->status,
            'status_label' => $this->statusLabel((string) $parent->status),
            'phone' => filled($parent->mobile_no) ? (string) $parent->mobile_no : 'Not recorded',
            'email' => filled($parent->email) ? (string) $parent->email : 'Not recorded',
            'last_login' => $this->lastLogin($schoolId, $parent->id),
            'whatsapp' => $optedIn ? 'Opted in' : 'Not opted in',
            'whatsapp_profile_name' => filled($whatsapp?->whatsapp_profile_name) ? (string) $whatsapp->whatsapp_profile_name : null,
            'children_count' => count($children),
            'children' => $children,
            'fees' => $fees,
        ];
    }

    public function deactivate(int $schoolId, string $name): User
    {
        $parent = $this->find($schoolId, $name);
        $parent->status = 'inactive';
        $parent->save();

        Userprofile::query()
            ->where('user_id', $parent->id)
            ->where('school_id', $schoolId)
            ->update(['status' => 'inactive']);

        return $parent->fresh(['userprofile']);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function children(int $schoolId, User $parent, ?string $currency): array
    {
        $yearId = AcademicYear::query()
            ->where('school_id', $schoolId)
            ->where('status', 1)
            ->orderByDesc('id')
            ->value('id');

        $links = $parent->children()
            ->where('school_id', $schoolId)
            ->with(['userStudent.userprofile'])
            ->get();

        $rows = [];
        foreach ($links as $link) {
            $student = $link->userStudent;
            if (! $student || (int) $student->school_id !== $schoolId || (int) $student->usergroup_id !== 6 || $student->status !== 'active') {
                continue;
            }
            $academic = null;
            if ($yearId) {
                $academic = StudentAcademic::query()
                    ->where('school_id', $schoolId)
                    ->where('user_id', $student->id)
                    ->where('academic_year_id', $yearId)
                    ->whereNull('deleted_at')
                    ->with('standardLink.section')
                    ->orderByDesc('id')
                    ->first();
            }
            $standardLink = $academic?->standardLink;
            if ($standardLink && (int) $standardLink->school_id !== $schoolId) {
                $standardLink = null;
            }
            $fees = $this->childFees($schoolId, $student->id, $standardLink?->standard_id);
            $rows[] = [
                'id' => $student->id,
                'name' => $this->personName($student, $student->userprofile),
                'class_name' => filled($standardLink?->section?->name) ? (string) $standardLink->section->name : 'No class yet',
                'kls' => filled($academic?->klassapp_student_id) ? (string) $academic->klassapp_student_id : 'Not recorded',
                'fees' => $this->moneyLine($fees, $currency),
                'profile_url' => url('/admin/students/'.$student->id),
                'report_url' => $standardLink
                    ? route('admin.reports.cards.student.preview', ['stdLink' => $standardLink->id, 'learner' => $student->id])
                    : null,
                'expected' => $fees['expected'],
                'paid' => $fees['paid'],
                'empty_fees' => $fees['empty'],
            ];
        }

        return $rows;
    }

    /**
     * @return array{empty: bool, expected: float, paid: float, balance: float}
     */
    private function childFees(int $schoolId, int $studentId, ?int $standardId): array
    {
        $expected = 0.0;
        if ($standardId) {
            $expected = (float) FeesCategories::query()
                ->where('school_id', $schoolId)
                ->where('standard_id', $standardId)
                ->sum('amount');
        }
        $paid = (float) FeePayment::query()
            ->where('school_id', $schoolId)
            ->where('user_id', $studentId)
            ->sum('amount');

        if ($expected <= 0 && $paid <= 0) {
            return ['empty' => true, 'expected' => 0.0, 'paid' => 0.0, 'balance' => 0.0];
        }

        return [
            'empty' => false,
            'expected' => $expected,
            'paid' => $paid,
            'balance' => max(0, $expected - $paid),
        ];
    }

    /**
     * The fees tile: the balance as the value, "Paid {currency} x of {currency} y" as the
     * detail and a status chip, the same as the student profile tile. The currency
     * shows on every amount.
     *
     * @param  list<array<string, mixed>>  $children
     * @return array{empty: bool, text: string, value: ?string, detail: ?string, status: ?string}
     */
    private function feesTile(array $children, ?string $currency): array
    {
        $expected = 0.0;
        $paid = 0.0;
        $any = false;
        foreach ($children as $child) {
            if ($child['empty_fees']) {
                continue;
            }
            $any = true;
            $expected += (float) $child['expected'];
            $paid += (float) $child['paid'];
        }
        if (! $any) {
            return ['empty' => true, 'text' => 'No fees recorded', 'value' => null, 'detail' => null, 'status' => null];
        }
        $balance = max(0, $expected - $paid);
        $value = $this->money($balance, $currency);
        $detail = $expected > 0 ? $this->paidOf($paid, $expected, $currency) : null;

        return [
            'empty' => false,
            'text' => $detail === null ? $value : $value.' · '.$detail,
            'value' => $value,
            'detail' => $detail,
            'status' => $this->feeStatus($expected, $paid),
        ];
    }

    /**
     * @param  array{empty: bool, expected: float, paid: float, balance: float}  $fees
     */
    private function moneyLine(array $fees, ?string $currency): string
    {
        if ($fees['empty']) {
            return 'No fees recorded';
        }
        $text = $this->money($fees['balance'], $currency);
        if ($fees['expected'] > 0) {
            $text .= ' · '.$this->paidOf($fees['paid'], $fees['expected'], $currency);
        }

        return $text;
    }

    private function money(float $amount, ?string $currency): string
    {
        return ($currency ? $currency.' ' : '').number_format($amount);
    }

    private function paidOf(float $paid, float $expected, ?string $currency): string
    {
        return 'Paid '.$this->money($paid, $currency).' of '.$this->money($expected, $currency);
    }

    private function feeStatus(float $expected, float $paid): string
    {
        if ($expected > 0 && $paid <= 0) {
            return 'Not paid';
        }
        if ($expected > 0 && $paid < $expected) {
            return 'Partly paid';
        }

        return 'Paid';
    }

    private function lastLogin(int $schoolId, int $parentId): string
    {
        $seen = ActivityLog::query()
            ->where('school_id', $schoolId)
            ->where('log_name', 'login')
            ->where('causer_id', $parentId)
            ->orderByDesc('id')
            ->value('created_at');

        if (! $seen) {
            return 'Never';
        }

        return Carbon::parse($seen)->timezone(config('app.timezone'))->format('j M Y');
    }

    private function personName(User $user, ?Userprofile $profile): string
    {
        $combined = '';
        if ($profile) {
            $combined = trim((string) $profile->firstname.' '.(string) $profile->lastname);
        }
        if ($combined === '') {
            $combined = trim((string) $user->full_name);
        }

        return $combined !== '' ? $this->titleLabel($combined) : 'Parent';
    }

    private function titleLabel(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return $value;
        }
        if ($value === mb_strtoupper($value) || $value === mb_strtolower($value)) {
            return mb_convert_case(mb_strtolower($value), MB_CASE_TITLE);
        }

        return $value;
    }

    private function initials(?string $first, ?string $last, string $fallback): string
    {
        $letters = '';
        foreach ([$first, $last] as $part) {
            $part = trim((string) $part);
            if ($part !== '') {
                $letters .= mb_strtoupper(mb_substr($part, 0, 1));
            }
        }
        if ($letters !== '') {
            return mb_substr($letters, 0, 2);
        }
        $word = trim($fallback);

        return $word !== '' ? mb_strtoupper(mb_substr($word, 0, 1)) : 'P';
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'active' => 'Active',
            'inactive' => 'Inactive',
            'exit' => 'Left',
            default => ucfirst($status),
        };
    }
}
