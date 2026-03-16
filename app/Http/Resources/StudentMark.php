<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StudentMark extends JsonResource
{


    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */

     //public static $wrap = 'user';
    public function toArray($request)
    {
       
       
        return 
        [
           
               $this->subject->name =>
               [
                    'Obtained Marks'    => $this->obtained_marks,
                    'Total Marks'       => $this->total_marks,

               ],
            
           
        ];
    }

   
}
