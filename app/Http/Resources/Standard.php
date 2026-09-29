<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Standard extends JsonResource
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
            'id'    =>  $this->id,
            // Raw class name (P.1-P.7, S.1-S.6, nursery word names). The legacy
            // integerToRoman() transform maps every real class name to an empty
            // string, which broke the public admission class list and the
            // class-dependent PLE/UCE fields.
            'name'  =>  (string) $this->name,
        ];
    }
}