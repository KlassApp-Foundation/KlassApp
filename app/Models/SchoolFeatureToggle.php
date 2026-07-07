<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolFeatureToggle extends Model
{
    protected $table = 'school_feature_toggles';

    protected $fillable = [
        'school_id',
        'feature',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
