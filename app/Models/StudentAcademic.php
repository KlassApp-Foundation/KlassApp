<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class StudentAcademic extends Model
{
    //
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'student_academics';


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'school_id' , 'academic_year_id' , 'user_id' , 'standardLink_id' , 'lin', 'std_school_pay_number' , 'klassapp_student_id' , 'id_card_number' , 'board_registration_number' , 'mode_of_transport' , 'transport_details' , 'siblings' , 'siblings_count' , 'sibling_details' , 'height' , 'weight' , 'medication_problems' , 'medication_needs' , 'medication_allergies' , 'food_allergies' , 'other_allergies' , 'other_medical_information' , 'academic_status','bus_pass'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'transport_details' => 'array' , 'sibling_details' => 'array',
    ];

    public function school()
    {
    	return $this->belongsTo('\App\Models\School','school_id');
    }

    public function academicYear()
    {
        return $this->belongsTo('\App\Models\AcademicYear','academic_year_id');
    }

    public function user()
    {
    	return $this->belongsTo('\App\Models\User','user_id');
    }

    public function standardLink()
    {
    	return $this->belongsTo('\App\Models\StandardLink','standardLink_id');
    }

    public function markUser()
    {
        return $this->hasMany('\App\Models\Mark','user_id','id');
    }

    public function markStandard()
    {
        return $this->hasMany('\App\Models\Mark','standard_id','id');
    }

    public function markAcademic()
    {
        return $this->hasMany('\App\Models\Mark','academic_year_id','id');
    }

    public function timetable()
    {
        return $this->hasMany('\App\Models\Timetable','academic_year_id','id');
    }

    /**
     * Validate klassapp_student_id format on save.
     * The KLS ID is the sole parent-to-child linking identifier for WhatsApp,
     * so it must match the format /^KLS\d{7}$/i or be null (for students
     * not yet assigned an ID). This guard prevents accidental corruption
     * via admin forms, mass-assignment, or direct Eloquent updates.
     */
    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if ($model->klassapp_student_id !== null
                && $model->klassapp_student_id !== ''
                && !preg_match('/^KLS\d{7}$/i', $model->klassapp_student_id)) {
                throw new \InvalidArgumentException(
                    "klassapp_student_id must match format KLS####### (e.g. KLS0010427), got: {$model->klassapp_student_id}"
                );
            }
        });
    }

}
