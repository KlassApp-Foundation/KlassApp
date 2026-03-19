<?php

namespace App\Http\Resources\API\Teacher;

use App\Http\Resources\API\Teacher\Attendance as AttendanceResource;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Helpers\SiteHelper;
use App\Models\Attendance;

class Studentlist extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $academic_year = SiteHelper::getAcademicYear($this->school_id);
        $attendances = Attendance::where([['school_id',$this->school_id],['academic_year_id',$academic_year->id],['standardLink_id',$this->standardLink_id],['user_id',$this->user_id]])->orderBy('date','DESC')->get()->take(10);
        $attendances = AttendanceResource::collection($attendances);
        
        return 
        [
            'standardLink_id'   =>  $this->standardLink_id,
            'student_id'        =>  $this->user_id,
            'student_name'      =>  $this->user->FullName,
            'avatar'            =>  $this->user->userprofile->AvatarPath,
            'attendance'        =>  $attendances,
        ];
    }
}