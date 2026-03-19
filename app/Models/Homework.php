<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\SiteHelper;
use App\Traits\Common;

class Homework extends Model
{
    use SoftDeletes;
    use Common;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table='homeworks';
	
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
	protected $fillable = [
	    'school_id' , 'academic_year_id' , 'standardLink_id' , 'section_id' , 'subject_id' , 'description' , 'attachment' , 'date' , 'created_by' , 'updated_by','teacher_id'
	];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    //protected $dates = [ 'date' , 'deleted_at'];

    protected $casts = [
        'date' => 'datetime',
        'deleted_at' => 'datetime',
    ];

	public function school()
    {
        return $this->belongsTo('\App\Models\School','school_id');
    }

    public function academicYear()
    {
        return $this->belongsTo('\App\Models\AcademicYear','academic_year_id');
    }

    public function standardLink()
    {
        return $this->belongsTo('\App\Models\StandardLink','standardLink_id');
    }

    public function subject()
    {
        return $this->belongsTo('\App\Models\Subject','subject_id');
    }

    public function createdBy()
    {
        return $this->belongsTo('\App\Models\User','created_by');
    }

    public function teacher()
    {
        return $this->belongsTo('\App\Models\User','teacher_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo('\App\Models\User','updated_by');
    }

    public function getAttachmentPathAttribute()
    {
        return $this->getFilePath($this->attachment);
    }

    public function studentHomework()
    {
        return $this->hasOne('\App\Models\StudentHomework','homework_id','id');
    }

    public function homeworkApproval()
    {
        return $this->hasOne('\App\Models\HomeworkApproval','homework_id','id');
    }

    public function viewers()
    {
        return $this->morphMany(StudentHistory::class, 'entity');
    }

    public function getFinishedCountAttribute()
    {
        $students       = SiteHelper::getClassStudents($this->school_id,$this->academic_year_id,$this->standardLink_id);
        $students_id    = $students->pluck('id')->toArray();
        $finished       = \App\Models\StudentHomework::where('homework_id',$this->id)->whereIn('user_id',$students_id)->count();
        
        return $finished;
    }

    public function getPendingCountAttribute()
    {
        $students       = SiteHelper::getClassStudents($this->school_id,$this->academic_year_id,$this->standardLink_id);
        $students_id    = $students->pluck('id')->toArray();
        $finished       = \App\Models\StudentHomework::where('homework_id',$this->id)->whereIn('user_id',$students_id)->count();
        $pending        = count($students_id) - $finished;
        
        return $pending;
    }

    public function getAttachmentTypeAttribute()
    {
        if($this->attachment != null && $this->attachment !='')
        {
            $attachment = $this->getFilePath($this->attachment);
            $extension=pathinfo( $attachment, PATHINFO_EXTENSION);//dd($extension);
            if(in_array($extension,['jpg','jpeg','png']))
            {
              $type='image';
            }
            elseif(in_array($extension,['mp3']))
            {
                $type='audio';
            }
            elseif(in_array($extension,['mp4']))
            {
                $type='video';
            }
            elseif(in_array($extension,['pdf']))
            {
                $type='pdf';
            }
            else
            {
                 $type='';
            }
        }
        else
        {
            $type='';
        }

        return $type;
    }
}