<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PremiumPage extends Model
{
    protected $fillable = [
        'school_id',
        'template_id',
        'is_published',
        'hero_image',
        'custom_colors',
        'custom_content',
        'seo_title',
        'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'template_id' => 'integer',
            'custom_colors' => 'array',
            'custom_content' => 'array',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
