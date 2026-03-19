<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Common;
class Discipline extends Model
{
    //
    use Common;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'disciplines';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'school_id' , 'academic_year_id' , 'user_id' ,'incident_date', 'reported_by', 'incident_detail','response','action_taken', 'attachments' , 'notify_parents' , 'is_seen'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    //protected $dates = ['deleted_at'];

    protected $casts = [
        'incident_date' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */
    public function school()
    {
        return $this->belongsTo('App\Models\School','school_id');
    }

    public function academicYear()
    {
        return $this->belongsTo('App\Models\AcademicYear','academic_year_id');
    }

    public function standardLink()
    {
        return $this->belongsTo('\App\Models\StandardLink','standardLink_id');
    }


    public function user()
    {
        return $this->belongsTo('App\Models\User','user_id');
    }

    public function teacher()
    {
        return $this->belongsTo('App\Models\User','reported_by');
    }

    public function getAttachmentPathAttribute()
    {
        return $this->getFilePath($this->attachments);
    }
}
