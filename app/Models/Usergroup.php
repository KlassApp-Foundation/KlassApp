<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usergroup extends Model
{
    //
    /**
     * The table associated with the model.
     *
     * @var string
     */
	protected $table = 'usergroups';

	public function user()
    {
        return $this->hasMany('App\Models\User','usergroup_id','id');
    }

    public function userprofile()
    {
        return $this->hasMany('App\Models\Userprofile','usergroup_id','id');
    }
}
