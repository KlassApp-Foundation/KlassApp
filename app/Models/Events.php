<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Common;

class Events extends Model
{
  use SoftDeletes;
  use Common;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table    = 'events';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'school_id' , 'academic_year_id' , 'standard_id' , 'select_type' , 'title' , 'description' , 'repeats' , 'freq' , 'freq_term' , 'location' , 'category' , 'organised_by' , 'image' , 'start_date' , 'end_date' , 'allDay' , 'url' , 'created_by' , 'updated_by','status'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    //protected $dates = ['start_date' ,  'end_date' , 'deleted_at'];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function school()
    {
        return $this->belongsTo('App\Models\School','school_id');
    }

    public function academicYear()
    {
        return $this->belongsTo('\App\Models\AcademicYear','academic_year_id');
    }

    public function standardlink()
    {
        return $this->belongsTo('App\Models\StandardLink','standard_id');
    }

    public function createdBy()
    {
      return $this->belongsTo('App\Models\User','created_by');
    }

    public function updatedBy()
    {
      return $this->belongsTo('App\Models\User','updated_by');
    }

    public function notes()
    {
        return $this->hasMany('App\Models\Notes','entity_id','id');
    }
    
    public function scopeByChurch($query,$church_id)
    {
        $query->where('church_id',$church_id);
        return $query;
    }

    public function eventreminder()
    {
        return $this->hasMany('App\Models\Reminder', 'entity_id','id')->where('entity_name','=','App\\Models\\Events');
    }

    public function eventgallery()
    {
        return $this->hasMany('App\Models\EventGallery','event_id','id');
    }

    public function getImagePathAttribute()
    {
        if($this->image==null) 
        {
            return $this->eventImagePath($this->category,$this->image);
        }
    }

    public function getphotocount($id,$school_id)
    {
       $count=EventGallery::where('school_id',$school_id)->where('event_id',$id)->count();

       return $count;
    }

    public function scopeByDate($query,$start_date,$end_date)
    {
       $query->where(function ($q) use ($start_date, $end_date) {
            $q->where('start_date', '>=', $start_date)
               ->where('start_date', '<', $end_date);

        })->orWhere(function ($q) use ($start_date, $end_date) {
            $q->where('start_date', '<=', $start_date)
               ->where('end_date', '>', $end_date);

        })->orWhere(function ($q) use ($start_date, $end_date) {
            $q->where('end_date', '>', $start_date)
               ->where('end_date', '<=', $end_date);

        })->orWhere(function ($q) use ($start_date, $end_date) {
            $q->where('start_date', '>=', $start_date)
               ->where('end_date', '<=', $end_date);

        });

        return $query;
    }
}