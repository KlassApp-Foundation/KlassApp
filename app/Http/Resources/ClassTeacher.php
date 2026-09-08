<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClassTeacher extends JsonResource
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
            'teacher_avatar'    =>  $this->teacher->userprofile->AvatarPath,
            // Username slug for /admin/teacher/show/{name}; never null (avoids /show/null).
            'teacher_name'      =>  $this->teacher->name ?: (string) $this->teacher->id,
            'teacher_fullname'  =>  $this->teacher->FullName,
            'subject_name'      =>  $this->subject->name,
        ];
    }
}
