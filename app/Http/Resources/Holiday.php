<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Holiday extends JsonResource
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
            'id'    =>  $this->id,
            'date'  =>  date('d-m-Y',strtotime($this->start_date)),
            'title' =>  $this->title,
        ];
    }
}
