<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use App\Models\Userprofile;

class Birthday extends JsonResource
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
          'age'        => $this->present()->getAge($this['date_of_birth']),
          'name'       => $this->user->name,
          'avatar'     => $this->AvatarPath,
          'firstname'  => $this->user->FullName,
       ];
    }
}
