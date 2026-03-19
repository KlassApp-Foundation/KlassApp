<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Common;
class NoticeBoard extends Model
{
    use SoftDeletes;
    use Common;


    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'notice_board';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'school_id' , 'academic_year_id' , 'standardLink_id' , 'type' ,'title', 'publish_date', 'expire_date','description','attachment_file', 'status'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    //protected $dates = ['publish_date' , 'expire_date'];

    protected $casts = [
        'publish_date' => 'datetime',
        'expire_date' => 'datetime',
    ];

    public function school()
    {
        return $this->belongsTo('App\Models\School','school_id');
    }

    public function backgroundimage()
    {
        return $this->belongsTo('App\Models\BackgroundImage','background_id');
    }

    public function academicYear()
    {
    	return $this->belongsTo('\App\Models\AcademicYear','academic_year_id');
    }

    public function standardLink()
    {
    	return $this->belongsTo('\App\Models\StandardLink','standardLink_id');
    }

    public function getAttachmentPathAttribute()
    {
        return $this->getFilePath($this->attachment_file);
    }
}
