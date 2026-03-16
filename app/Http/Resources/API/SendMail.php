<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Resources\Json\JsonResource;

class SendMail extends JsonResource
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
            'subject'   =>  $this->subject,
            'message'   =>  $this->message,
            'sentAt'    =>  $this->fired_at->diffForHumans(),
        ];
    }
}
