<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Resources\Json\JsonResource;

class Academic extends JsonResource
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
        'id'          =>  $this->id,
        'schoolId'   =>  $this->school_id,
        'name'        =>  $this->name,
        'description' =>  $this->description,
        'status'      =>  $this->status,
      ];
   }
}