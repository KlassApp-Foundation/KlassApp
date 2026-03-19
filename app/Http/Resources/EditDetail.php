<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EditDetail extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /*if($this->meta_key == 'school_logo')
        {
            $value = $this->LogoPath;
        }
        else
        {
            $value = $this->meta_value;
        }*/
        return 
        [
            $this->meta_key     => $this->meta_value,
        ];
    }
}