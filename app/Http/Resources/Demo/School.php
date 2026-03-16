<?php

namespace App\Http\Resources\Demo;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Helpers\SiteHelper;

class School extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $academic_year = SiteHelper::getAcademicYear($this->id);
        return [
            'id'                =>  $this->id,
            'name'              =>  $this->name,
            'email'             =>  $this->email,
            'phone'             =>  $this->phone,
            'board'             =>  $this->schoolDetailBoard->meta_value == '-' ? null:$this->schoolDetailBoard->meta_value,
            'academic_year_id'  =>  $academic_year->id, 
        ];
    }
}