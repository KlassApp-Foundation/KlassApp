<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeesCategories extends Model
{
    use HasFactory;
    protected $fillable = [
        "school_id", "standard_id", "section_id", "academic_term_id", "name", "amount",
        "due_date", "enable_installments", "max_installments", "deposit_required",
        "late_fee_percent", "late_fee_flat", "grace_days",
        "discount_percent", "discount_flat", "discount_until",
        "fee_type",
    ];

    protected $casts = [
        "amount"              => "decimal:2",
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

    /**
     * Display label used in payment UIs: "Tuition (nursery)", "Tuition (O'Level)", etc.
     * Same pattern as Round 3 fee-category disambiguation — standard name in parentheses,
     * with human-friendly O/A Level labels for secondary tiers.
     */
    public function labeledName(): string
    {
        $tier = match ($this->standard?->name) {
            'o-level' => "O'Level",
            'a-level' => "A'Level",
            default => $this->standard?->name,
        };

        return $tier ? $this->name.' ('.$tier.')' : (string) $this->name;
    }

}
