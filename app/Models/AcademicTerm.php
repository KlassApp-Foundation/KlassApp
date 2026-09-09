<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicTerm extends Model
{
    use HasFactory;

    protected $fillable = ["school_id", "academic_year_id", "name", "starts_on", "ends_on", "status"];

    protected $casts = [
        "starts_on" => "date:Y-d-m",
        "ends_on" => "date:Y-d-m"
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    /**
     * Ordered term ids for a school year → 1-based ordinals + total count.
     * Replaces hardcoded First/Second/Third → 1/2/3 maps in marks Blade views.
     *
     * @return array{count: int, ordinals: array<int, int>}
     */
    public static function ordinalsForYear(int $schoolId, int $academicYearId): array
    {
        $ids = static::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->orderByRaw('starts_on is null')
            ->orderBy('starts_on')
            ->orderBy('id')
            ->pluck('id');

        $ordinals = [];
        foreach ($ids->values() as $index => $id) {
            $ordinals[(int) $id] = $index + 1;
        }

        return [
            'count' => $ids->count(),
            'ordinals' => $ordinals,
        ];
    }

    /**
     * Display like "2 of 2" from the school's configured terms (not a hardcoded 3).
     */
    public function positionLabel(): string
    {
        $meta = static::ordinalsForYear((int) $this->school_id, (int) $this->academic_year_id);
        $ordinal = $meta['ordinals'][$this->id] ?? null;

        if ($ordinal === null || $meta['count'] < 1) {
            return (string) $this->name;
        }

        return $ordinal.' of '.$meta['count'];
    }
}
