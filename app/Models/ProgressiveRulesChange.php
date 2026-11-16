<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A row in the "last changed by" history of the progressive report rules. */
class ProgressiveRulesChange extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['school_id', 'standard_id', 'user_id', 'action', 'summary'];

    protected $casts = ['created_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
