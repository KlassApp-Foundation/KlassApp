<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VideoRoomUser extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return 
        [
            'id'            => $this->id,         
            'fullname'      => $this->FullName,
            'email'         => $this->email,
            'mobile_no'     => $this->mobile_no, 
            'class'         => $this->studentAcademicLatest->standardLink->StandardSection,
            'std_school_pay_number'   => $this->studentAcademicLatest->std_school_pay_number,
        ];
    }
}