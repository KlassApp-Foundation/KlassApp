<?php

namespace App\Services\Demo;

use App\Models\School;
use App\Models\SchoolDetail;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Support\DemoSeedManifest;
use App\Support\DemoStudentCode;
use Illuminate\Support\Collection;

/**
 * The public follow-along page (/demo) and the WhatsApp BIND code share this.
 *
 * Only is_demo schools are ever read. The page school is the Try Demo
 * parent's school (services.whatsapp.demo_parent_user_id) when that school is
 * a demo school; otherwise the demo school made by demo:provision; otherwise
 * the first demo school.
 */
final class DemoFollowAlong
{
    /**
     * At most this many students are listed on the page. Was 30; raised so a
     * live demo with about 50 attendees (demo:provision seeds 70
     * students with marks) can show every code on one page.
     */
    public const ROSTER_LIMIT = 120;

    /**
     * demo:expire-chats runs every minute. A binding is removed one interval
     * before its TTL, so a number is never kept longer than the TTL.
     */
    public const EXPIRY_INTERVAL_MINUTES = 1;

    public static function bindTtlMinutes(): int
    {
        $ttl = (int) config('klassapp.demo.bind_ttl_minutes', 45);

        return $ttl >= 2 ? $ttl : 45;
    }

    /** Bindings made at or before this instant are expired. */
    public static function bindCutoff(): \Illuminate\Support\Carbon
    {
        return now()->subMinutes(self::bindTtlMinutes() - self::EXPIRY_INTERVAL_MINUTES);
    }

    /**
     * When a BIND started at $start (a pending request's created_at, or a
     * binding's demo_linked_at) is removed by demo:expire-chats: the same
     * instant bindCutoff() checks, seen from the start instead of from now.
     */
    public static function expiresAt(\DateTimeInterface $start): \Illuminate\Support\Carbon
    {
        return \Illuminate\Support\Carbon::instance($start)->addMinutes(self::bindTtlMinutes() - self::EXPIRY_INTERVAL_MINUTES);
    }

    /**
     * The columns an unlink clears on a whatsapp_users row: demo:expire-chats,
     * demo:nightly-reset and the admin Unlink button all write exactly these,
     * so the row is kept but the number follows nobody.
     *
     * @return array<string, mixed>
     */
    public static function unlinkAttributes(): array
    {
        return [
            'user_id' => null,
            'school_id' => null,
            'opted_in' => false,
            'demo_linked_at' => null,
            'demo_student_id' => null,
        ];
    }

    public static function removalNotice(): string
    {
        return 'Your number is removed after ' . self::bindTtlMinutes() . ' minutes.';
    }

    public static function privacyUrl(): string
    {
        return trim((string) config('services.whatsapp.privacy_notice_url'));
    }

    public static function pageUrl(): string
    {
        // From APP_URL, not the request: replies are built while answering
        // a webhook, whose request host is not the public site.
        return rtrim((string) config('app.url'), '/') . '/demo';
    }

    /** Digits only, as wa.me expects. Empty when WHATSAPP_DEMO_NUMBER is not set. */
    public static function whatsappDigits(): string
    {
        return (string) preg_replace('/\D+/', '', (string) config('klassapp.demo.whatsapp_number'));
    }

    public static function waLink(string $text): ?string
    {
        $digits = self::whatsappDigits();
        if ($digits === '') {
            return null;
        }

        return 'https://wa.me/' . $digits . '?text=' . rawurlencode($text);
    }

    /** @return array<int, array{label:string, url:string}> */
    public static function links(): array
    {
        $links = [];
        foreach ((array) config('klassapp.demo.links', []) as $link) {
            $url = trim((string) ($link['url'] ?? ''));
            if ($url !== '') {
                $links[] = ['label' => (string) ($link['label'] ?? $url), 'url' => $url];
            }
        }
        if (self::privacyUrl() !== '') {
            $links[] = ['label' => 'Privacy notice', 'url' => self::privacyUrl()];
        }

        return $links;
    }

    public static function school(): ?School
    {
        $parentId = (int) config('services.whatsapp.demo_parent_user_id');
        if ($parentId > 0) {
            $schoolId = User::query()->whereKey($parentId)->value('school_id');
            $school = $schoolId ? School::query()->whereKey($schoolId)->where('is_demo', 1)->first() : null;
            if ($school) {
                return $school;
            }
        }

        $demoIds = School::query()->where('is_demo', 1)->orderBy('id')->pluck('id');
        if ($demoIds->isEmpty()) {
            return null;
        }

        $provisioned = SchoolDetail::query()
            ->whereIn('school_id', $demoIds)
            ->where('meta_key', DemoSeedManifest::META_KEY)
            ->orderByDesc('school_id')
            ->get(['school_id', 'meta_value'])
            ->first(fn ($row) => is_array(json_decode((string) $row->meta_value, true)['provision'] ?? null));

        return School::query()->whereKey($provisioned?->school_id ?? $demoIds->first())->first();
    }

    /**
     * Students of the demo school whose class has a latest exam with marks,
     * highest class first.
     *
     * @return Collection<int, array{code:string, first_name:string, class:string}>
     */
    public static function roster(?School $school): Collection
    {
        if (! $school || (int) $school->is_demo !== 1) {
            return collect();
        }

        $schoolId = (int) $school->id;
        $academics = StudentAcademic::query()
            ->where('school_id', $schoolId)
            ->whereIn('academic_year_id', function ($q) use ($schoolId) {
                $q->select('id')->from('academic_years')->where('school_id', $schoolId)->where('status', 1);
            })
            ->whereHas('user', fn ($q) => $q->where('school_id', $schoolId)->where('usergroup_id', 6)->where('status', 'active'))
            ->with(['user.userprofile', 'standardLink.section'])
            ->get();

        $hasExam = [];
        $rows = collect();
        foreach ($academics as $academic) {
            $link = $academic->standardLink;
            $section = $link?->section;
            if (! $link || ! $section || (int) $link->school_id !== $schoolId) {
                continue;
            }
            $sectionId = (int) $section->id;
            $hasExam[$sectionId] ??= LatestExamMarks::forSection($schoolId, $sectionId) !== null;
            if (! $hasExam[$sectionId]) {
                continue;
            }
            $code = DemoStudentCode::fromKls($academic->user->registration_number ?: $academic->klassapp_student_id);
            if ($code === null) {
                continue;
            }
            $first = trim((string) ($academic->user->userprofile?->getRawOriginal('firstname') ?? ''));
            if ($first === '') {
                $first = (string) strtok((string) $academic->user->name, ' ');
            }
            $rows->push([
                'code' => $code,
                'first_name' => \Illuminate\Support\Str::title(mb_strtolower($first)),
                'class' => trim($section->name . ' ' . ($link->stream ?? '')),
                'section' => (string) $section->name,
            ]);
        }

        return $rows
            ->sort(fn ($a, $b) => strnatcasecmp($b['section'], $a['section']) ?: strcmp($a['class'], $b['class']) ?: strcmp($a['first_name'], $b['first_name']))
            ->take(self::ROSTER_LIMIT)
            ->map(fn ($row) => ['code' => $row['code'], 'first_name' => $row['first_name'], 'class' => $row['class']])
            ->values();
    }
}
