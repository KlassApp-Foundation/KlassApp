<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeesCategories extends Model
{
    use HasFactory;

    public const DURATION_ONE_OFF = 'one_off';

    public const DURATION_TERMLY = 'termly';

    public const DURATION_YEARLY = 'yearly';

    protected $fillable = [
        "school_id", "standard_id", "section_id", "academic_term_id", "name", "amount",
        "due_date", "enable_installments", "max_installments", "deposit_required",
        "late_fee_percent", "late_fee_flat", "grace_days",
        "discount_percent", "discount_flat", "discount_until",
        "fee_type", "duration", "applies_school_wide",
    ];

    protected $casts = [
        "amount"              => "decimal:2",
        "applies_school_wide" => "boolean",
        "due_date"            => "date",
        "enable_installments" => "boolean",
        "deposit_required"    => "decimal:2",
        "late_fee_percent"    => "decimal:2",
        "late_fee_flat"       => "decimal:2",
        "discount_percent"    => "decimal:2",
        "discount_flat"       => "decimal:2",
        "discount_until"      => "date",
    ];

    public function school(){
        return $this->belongsTo(School::class, "school_id");
    }

     public function standard(){
        return $this->belongsTo(Standard::class, "standard_id");
    }

    public function section(){
        return $this->belongsTo(Section::class, "section_id");
    }

    public function term(){
        return $this->belongsTo(AcademicTerm::class, "academic_term_id");
    }

    public function classes()
    {
        return $this->belongsToMany(Section::class, 'fee_category_sections', 'fee_category_id', 'section_id');
    }

    /**
     * @return array<string, string>
     */
    public static function durations(): array
    {
        return [
            self::DURATION_ONE_OFF => 'One-off',
            self::DURATION_TERMLY => 'Termly',
            self::DURATION_YEARLY => 'Yearly',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function types(): array
    {
        return [
            'tuition' => 'Tuition',
            'development' => 'Development',
            'lunch' => 'Lunch',
            'transport' => 'Transport',
            'exam' => 'Exam',
            'other' => 'Other',
        ];
    }

    public function durationLabel(): string
    {
        return self::durations()[$this->duration] ?? 'Termly';
    }

    public function typeLabel(): string
    {
        $type = (string) ($this->fee_type ?: 'tuition');

        return self::types()[$type] ?? ucfirst($type);
    }

    /**
     * Human-friendly grading-tier label for a Standard name.
     * Used by payment UIs and Toshi/wizard fee-level selectors — keep in one place.
     */
    public static function tierDisplayLabel(?string $standardName): ?string
    {
        if ($standardName === null || $standardName === '') {
            return null;
        }

        return match ($standardName) {
            'o-level' => "O'Level",
            'a-level' => "A'Level",
            'all' => 'All Levels',
            'secondary' => "Secondary (O'+A')",
            'nursery' => 'Nursery',
            'primary' => 'Primary',
            default => $standardName,
        };
    }

    /**
     * Display label used in payment UIs: "Tuition (nursery)", "Tuition (O'Level)", etc.
     * Same pattern as Round 3 fee-category disambiguation — standard name in parentheses,
     * with human-friendly O/A Level labels for secondary tiers.
     */
    public function labeledName(): string
    {
        if ($this->applies_school_wide) {
            return $this->name.' (All classes)';
        }

        $tier = self::tierDisplayLabel($this->standard?->name);

        return $tier ? $this->name.' ('.$tier.')' : (string) $this->name;
    }

}
