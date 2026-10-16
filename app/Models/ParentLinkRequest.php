<?php

namespace App\Models;

use App\Models\Concerns\HasApprovals;
use App\Models\Contracts\Approvable;
use App\Services\Demo\DemoBindApproval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentLinkRequest extends Model implements Approvable
{
    use HasApprovals;

    protected $fillable = [
        'school_id',
        'phone',
        'parent_name',
        'whatsapp_profile_name',
        'child_name',
        'child_class',
        'school_name',
        'suggested_student_id',
        'matched_student_id',
        'status',
        'flow_token',
        'source',
        'candidate_student_ids',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'candidate_student_ids' => 'array',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function suggestedStudent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suggested_student_id');
    }

    public function matchedStudent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'matched_student_id');
    }

    public function displayType(): string
    {
        return 'Parent link (WhatsApp)';
    }

    public function summaryLine(): string
    {
        $school = $this->school_name
            ?: $this->school?->name
            ?: 'unknown school';

        return "{$this->parent_name} → {$this->child_name} ({$this->child_class}) @ {$school}";
    }

    /**
     * Display name for confirmation / inbox — prefers resolved school, then submitted text.
     */
    public function schoolDisplayName(): string
    {
        return $this->school?->name
            ?: ($this->school_name !== null && $this->school_name !== '' ? $this->school_name : 'the school');
    }

    /**
     * The name Meta put on the message (contacts[].profile.name). Our own
     * fallbacks ("Demo User", "Parent") are not a profile name.
     */
    public static function profileNameOrNull(?string $name): ?string
    {
        $name = trim((string) $name);
        if ($name === '' || in_array($name, ['Demo User', 'Parent', 'Demo guest'], true)) {
            return null;
        }

        return mb_substr($name, 0, 150);
    }

    /** klassapp_number, form, or demo. Older rows are inferred from the token. */
    public function sourceKey(): string
    {
        $source = (string) $this->source;
        if (in_array($source, ['klassapp_number', 'form', 'demo'], true)) {
            return $source;
        }

        return $this->flow_token === DemoBindApproval::REQUEST_TOKEN ? 'demo' : 'form';
    }

    public function sourceLabel(): string
    {
        return match ($this->sourceKey()) {
            'klassapp_number' => 'KlassApp number',
            'demo' => 'Demo',
            default => 'Form',
        };
    }

    /**
     * Name on the request card. A form uses the name the parent typed.
     * Anything else uses the WhatsApp profile name, then the number.
     */
    public function cardParentName(): string
    {
        if ($this->sourceKey() === 'form') {
            $typed = trim((string) $this->parent_name);
            if ($typed !== '') {
                return $typed;
            }
        }

        $profile = trim((string) $this->whatsapp_profile_name);
        if ($profile !== '') {
            return $profile;
        }

        $fallback = trim((string) $this->parent_name);
        if ($fallback !== '' && self::profileNameOrNull($fallback) !== null) {
            return $fallback;
        }

        return '';
    }
}
