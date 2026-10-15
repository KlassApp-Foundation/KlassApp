<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EarlyAccessRequest extends Model
{
    protected $table = 'early_access_requests';

    protected $fillable = [
        'school_name',
        'contact_name',
        'contact_role',
        'email',
        'phone',
        'country',
        'approx_students',
        'consented_at',
    ];

    protected $casts = [
        'approx_students' => 'integer',
        'consented_at' => 'datetime',
    ];
}
