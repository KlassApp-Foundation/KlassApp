<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Common;

class Bulletin extends Model
{
    //

    use SoftDeletes;
    use Common;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'magazines';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'school_id' , 'academic_year_id' , 'name' , 'year' , 'bulletin_file' 
    ];

    public function school()
    {
        return $this->belongsTo('App\Models\School','school_id');
    }

    public function academicYear()
    {
        return $this->belongsTo('\App\Models\AcademicYear','academic_year_id');
    }

     public function getFilePathAttribute()
    {
        return $this->getFilePath($this->bulletin_file);
    }

    public function getImagePathAttribute()
    {
        return $this->getFilePath($this->cover_image);
    }
}