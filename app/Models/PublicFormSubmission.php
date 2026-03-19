<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicFormSubmission extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'public_form_submissions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'form_name',
        'source_url',
        'ip_address',
        'user_agent',
        'payload',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'payload' => 'array',
    ];
}
