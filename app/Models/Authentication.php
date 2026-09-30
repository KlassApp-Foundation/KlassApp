<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Authentication extends Model
{
    //
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'authentications';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id' , 'type' , 'token' , 'ip_address' , 'expires_on' , 'status' , 'attempts' , 'locked_until'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $casts = [
        'expires_on' => 'datetime',
        'locked_until' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User','user_id');
    }
}