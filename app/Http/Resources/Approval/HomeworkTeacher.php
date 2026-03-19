<?php

namespace App\Http\Resources\Approval;

use Illuminate\Http\Resources\Json\JsonResource;

class HomeworkTeacher extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            //
            'id'        =>  $this->teacher_id,
            'name'      =>  $this->teacher->name,
            'fullname'  =>  $this->teacher->FullName,
        ];
    }
}