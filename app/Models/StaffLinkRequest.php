<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffLinkRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    protected $fillable = ['school_id', 'name', 'email', 'phone', 'status', 'reviewed_by', 'reviewed_at'];

    protected $casts = ['reviewed_at' => 'datetime'];
}
