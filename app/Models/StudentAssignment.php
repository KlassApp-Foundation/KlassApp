<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Common;

class StudentAssignment extends Model
{
    //
    use SoftDeletes;
    use Common;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'student_assignments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'assignment_id' , 'user_id' , 'assignment_file' , 'obtained_marks' , 'submitted_on' ,'comments' , 'marks_given_by' , 'marks_given_on' , 'status'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['submitted_on' , 'marks_given_on' , 'deleted_at'];

    protected $casts=[ 'assignment_file' => 'array' ];

    public function assignment()
    {
        return $this->belongsTo('App\Models\Assignment','assignment_id');
    }

    public function student()
    {
    	return $this->belongsTo('\App\Models\User','user_id');
    }

    public function teacher()
    {
    	return $this->belongsTo('\App\Models\User','marks_given_by');
    }

    public function getAssignmentFilePathAttribute()
    {
        $count = count($this->assignment_file);
        for($i=1 ; $i <= $count ; $i++)
        {
            $attachment[$i]['original_path']    = $this->assignment_file[$i];
            $attachment[$i]['path']             = $this->getFilePath($this->assignment_file[$i]);
            $attachment[$i]['id']               = $i;
        }
        return $attachment;
    }
}