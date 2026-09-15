<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Teacher extends JsonResource
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
            //
            'id'                =>  $this->id,
            'name'              =>  $this->name ?: (string) $this->id,
            'email'             =>  $this->email,
            'mobile_no'         =>  $this->mobile_no,
            'avatar'            =>  optional($this->userprofile)->AvatarPath ?: null,
            'fullname'          =>  $this->FullName,
            'designation'       =>  $details['designation'] ?? null,
            'designation_name'  =>  $details['designation_name'] ?? null,
            'sub_designation'   =>  $details['sub_designation'] ?? null,
            'date_of_birth'     =>  blank(optional($this->userprofile)->date_of_birth)
                ? null
                : date('d M Y', strtotime($this->userprofile->date_of_birth)),
            'status'            =>  $this->status,
        ];
    }
}
