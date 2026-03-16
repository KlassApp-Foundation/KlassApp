<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;

class FeedbackMessage extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $admin = User::where('school_id',$this->school_id)->ByRole(3)->first();
        $show = [];
        
        if($this->user_id == $admin->id)
        {
            $show['type']    = "receive";
            $show['time']    = date('d-m-Y H:i:s',strtotime($this->created_at));
            $show['message'] = $this->message;    
        }
        else
        {
            $show['type']    = "send";
            $show['time']    = date('d-m-Y H:i:s',strtotime($this->created_at));
            $show['message'] = $this->message;           
        }
        
        return $show;
    }
}
