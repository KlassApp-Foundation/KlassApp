<?php

namespace App\Http\Resources;

use App\Services\OnboardingEngine;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;

class UserDetail extends JsonResource
{

   /**
    * Transform the resource into an array.
    *
    * @param  \Illuminate\Http\Request  $request
    * @return array
    */
    public function toArray($request)
    {
       if(optional($this->userprofile)->avatar=='')
    {
        $avatarpath = \Storage::url('uploads/admin/member/avatar/images.jpg');
    }
    else
    {
        $avatarpath = optional($this->userprofile)->AvatarPath;
    }
        $standardName = $this->studentAcademicLatest->standardLink->standard->name ?? '';
        $sectionName = $this->studentAcademicLatest->standardLink->section->name ?? '';
        $isCandidateClass = OnboardingEngine::isCandidateClass($standardName)
            || OnboardingEngine::isCandidateClass($sectionName);

        if ($isCandidateClass)
        {
            $board_registration_number = optional($this->studentAcademicLatest)->board_registration_number;
        }
        else
        {
            $board_registration_number = null;
        }

        return
        [
            'name'                      => $this->name,
            'school_name'               => optional($this->school)->name,
            'fullname'                  => $this->FullName,
            'gender'                    => optional($this->userprofile)->gender,
            'date_of_birth'             => \App\Support\DateOfBirth::formatOrNull(optional($this->userprofile)->date_of_birth, 'd-m-Y'),
            'address'                   => optional($this->userprofile)->address,
            'city'                      => optional(optional($this->userprofile)->city)->name,
            'country'                   => optional(optional($this->userprofile)->country)->name,
            'pincode'                   => optional($this->userprofile)->pincode=="" ? null:optional($this->userprofile)->pincode,
            'email'                     => $this->email,
            'mobile_no'                 => $this->mobile_no,
            'notes'                     => optional($this->userprofile)->notes=="" ? null:optional($this->userprofile)->notes,
            'avatar'                    => $avatarpath,
            'created_at'                => optional($this->userprofile)->created_at=="" ? null:date('d-m-Y H:i:s',strtotime(optional($this->userprofile)->created_at)),
            'updated_at'                => optional($this->userprofile)->updated_at=="" ? null:date('d-m-Y H:i:s',strtotime(optional($this->userprofile)->updated_at)),
            'age'                       => \App\Support\DateOfBirth::age(optional($this->userprofile)->date_of_birth),
            'ref_id'                    => $this->ref_id,
            'class'                     => $this->studentAcademicLatest?->standardLink?->StandardSection ?? 'No class',
            'transport_mode'            => ucwords(str_replace('_', ' ', optional($this->studentAcademicLatest)->mode_of_transport)),
            'driver_name'               => optional($this->studentAcademicLatest)->transport_details['driver_name'] ?? null,
            'driver_number'             => optional($this->studentAcademicLatest)->transport_details['driver_contact_number'] ?? null,
            'registration_number'       => $this->registration_number == null ? optional($this->userprofile)->registration_number:$this->registration_number,
            'lin'               => optional($this->userprofile)->lin,
            'joining_date'           => \App\Support\DateOfBirth::formatOrNull(optional($this->userprofile)->joining_date),
            'std_school_pay_number'               => optional($this->studentAcademicLatest)->std_school_pay_number,
            'school_student_id'          => optional($this->studentAcademicLatest)->school_student_id,
            'board_registration_number' => $board_registration_number,
            'is_candidate_class'        => $isCandidateClass,
            'librarycard_number'        => optional($this->librarycard)->library_card_no,
        ];
    }
}
