<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Common;

class StudentHomework extends Model
{
    //
    use SoftDeletes;
    use Common;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'student_homework';


    /**
     * The attributes that are mass assignable.
     * 
     * @var array
     */
    protected $fillable = [
        'homework_id' , 'user_id' , 'attachment' , 'submitted_on' , 'checked_by' , 'checked_on' , 'status' , 'comments' 
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['submitted_on' , 'checked_on' , 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts=[ 'attachment' => 'array' ];

    public function homework()
    {
        return $this->belongsTo('App\Models\Homework','homework_id');
    }

    public function student()
    {
    	return $this->belongsTo('\App\Models\User','user_id');
    }

    public function teacher()
    {
        return $this->belongsTo('\App\Models\User','checked_by');
    }

    public function getAttachmentPathAttribute()
    {
        $count = count($this->attachment);
        for($i=1 ; $i <= $count ; $i++)
        {
            $attachment[$i]['original_path']    = $this->attachment[$i];
            $attachment[$i]['path']             = $this->getFilePath($this->attachment[$i]);
            $attachment[$i]['id']               = $i;
        }
        return $attachment;
    }
}