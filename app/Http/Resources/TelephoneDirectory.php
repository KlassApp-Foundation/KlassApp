<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TelephoneDirectory extends JsonResource
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
        'id'          => $this->id,
        'school_id'   => $this->school_id,
        'name'        => $this->name,
        'designation' => $this->designation,
        'phone_number'=> $this->phone_number,
       
       ];
   }
}