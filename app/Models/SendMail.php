<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SendMail extends Model
{
    //
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'send_mail';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'school_id' , 'academic_year_id' , 'user_id' , 'from_address' , 'from' , 'to' , 'subject' , 'message' , 'attachments' , 'status' , 'type' , 'message_id' , 'executed_at' , 'is_executed' , 'fired_at' , 'read_at'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['executed_at' , 'fired_at' , 'read_at' , 'deleted_at'];

    public function school()
    {
        return $this->belongsTo('App\Models\School','school_id');
    }

    public function academicYear()
    {
        return $this->belongsTo('App\Models\AcademicYear','academic_year_id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User','user_id');
    }

    public function student()
    {
        return $this->belongsTo('App\Models\User','student_id');
    }

}
