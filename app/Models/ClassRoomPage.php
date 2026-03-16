<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Common;

class ClassRoomPage extends Model
{
    //
    use SoftDeletes;
    use Common;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'class_room_pages';


    /**
     * The attributes that are mass assignable.
     * 
     * @var array
     */
    protected $fillable = [
        'school_id' , 'academic_year_id' , 'page_name' , 'category_id' , 'description' , 'cover_image' , 'created_by' , 'status'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

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
    	return $this->belongsTo('\App\Models\User','created_by');
    }

    public function classRoomPageDetail()
    {
    	return $this->hasMany('\App\Models\ClassRoomPageDetail','page_id','id');
    }

    public function classRoomPageAttachment()
    {
    	return $this->hasMany('\App\Models\ClassRoomPageAttachment','page_id','id');
    }

    public function classRoomPageCategory()
    {
        return $this->belongsTo('\App\Models\ClassRoomPageCategory','category_id');
    }

    public function getCoverImagePathAttribute()
    {
        return $this->getFilePath($this->cover_image);
    }
}