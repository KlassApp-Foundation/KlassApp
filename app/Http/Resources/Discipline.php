<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class Discipline extends JsonResource
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
            'id'                =>  $this->id,
            'incident_date'     =>  date('d-m-Y H:i:s',strtotime($this->incident_date)),
            'incident_detail'   =>  Str::limit($this->incident_detail,10,'...'),
            'teacher_username'  =>  $this->teacher->name,
            'teacher_name'      =>  ucwords($this->teacher->FullName),
            'attachment'        =>  $this->attachments == null ? null:$this->AttachmentPath,
        ];
    }
}
