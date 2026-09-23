<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Attendance extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // An attendance row can outlive the user it points at (removed or orphaned
        // account), and the relations below used to be dereferenced unconditionally, so
        // serialising such a row logged "Attempt to read property ... on null" and could
        // produce broken output. The user fields degrade to a clear placeholder, the other
        // relations to a dash, and the record still serialises.
        $user = $this->user;
        $admin = $this->admin;

        return 
        [
            //
            'user_id'       =>  $this->user_id,
            'user_name'     =>  $user ? ($user->displayName ?: $user->name) : 'Unknown student',
            'user_fullname' =>  $user ? ($user->FullName ?: $user->name) : 'Unknown student',
            'date'          =>  date('d M Y',strtotime($this->date)),
            'id_date'       =>  date('d_m_y',strtotime($this->date)).'_'.$this->session,
            'session'       =>  ucfirst($this->session),
            'reason'        =>  $this->absentReason->title ?? '-',
            'remarks'       =>  $this->remarks,
            'recorded_by'   =>  $admin ? ucfirst($admin->FullName ?: $admin->name) : '-',
            'created_at'    =>  date('d M Y',strtotime($this->created_at)),
            'class'         =>  $this->standardLink->StandardSection ?? '-',
            'class_id'      =>  $this->standardLink_id,
        ];
    }
}
