<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use App\Helpers\SiteHelper;
use App\Models\Timetable;

class TeacherDetail extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $details = $this->getTeacherDetails();
        return 
        [
            'id'                => $this->id,
            'name'              => $this->name,
            'mobile_no'         => $this->mobile_no,
            'avatar'            => optional($this->userprofile)->AvatarPath ?: null,
            'fullname'          => $this->FullName,
            'city'              => optional(optional($this->userprofile)->city)->name,
            'joining_date'      => \App\Support\DateOfBirth::formatOrNull(optional($this->userprofile)->joining_date, 'Y-m-d'),
            'age'               => \App\Support\DateOfBirth::age(optional($this->userprofile)->date_of_birth),
            'marital_status'    => blank(optional($this->userprofile)->marital_status) ? null : ucwords($this->userprofile->marital_status),
            'details'           => $details,
            'designation_name'  => $details['designation_name'] ?? null,
            'designation'       => $details['designation'] ?? null,
            'sub_designation'   => $details['sub_designation'] ?? null,
            'employee_id'       => $details['employee_id'] ?? null,
            'status'            => $details['status'] ?? null,
            'class_teacher'     => optional($this->standardLink)->StandardSection,
            'job_type'          => $details['job_type'] ?? null,
            'interested_in'     => $details['interested_in'] ?? null,
        ];
    }
}
