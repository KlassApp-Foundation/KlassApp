<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Children extends JsonResource
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
            //
            'name'      =>  $this->userStudent->name,
            'fullname'  =>  $this->userStudent->FullName,
            'class'     =>  $this->userStudent->studentAcademicLatest->standardLink->StandardSection,
            'avatar'    =>  $this->userStudent->userprofile->AvatarPath,
        ];
    }
}
