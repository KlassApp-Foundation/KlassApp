<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemoRequest extends Model
{
    protected $table = 'demo_requests';

    protected $fillable = [
        'school_name',
        'contact_name',
        'phone',
        'email',
        'district',
        'message',
        'source_page',
    ];
}
