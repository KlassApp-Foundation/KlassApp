<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\API\ShowEventGallery as ShowEventGalleryResource;
class ShowEvent extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
         if($this->standard_id!=null)
            {
                $class_name=$this->standardlink->standardsection;

            }else{
                  $class_name=null;
            }

       return [

            'event_id' => $this->id,
            'school_id' =>$this->school_id,
            'standard_id'       => $this->standard_id,
            'select_type' =>$this->select_type,
            'title' => $this->title,
            'description' => $this->description,
            'repeats'=>$this->repeats,
            'freq' => $this->freq,
            'freq_term' =>$this->freq_term,
            'location' =>$this->location,
            'category' =>$this->category,
            'image' =>$this->ImagePath,
            'photos'=>$this->getphotocount($this->id,$this->school_id),
            'start_date' =>date('d-m-Y H:i:s', strtotime($this->start_date)),
            'end_date' => date('d-m-Y H:i:s', strtotime($this->end_date)),
            'eventgallery'=>ShowEventGallery::collection($this->eventgallery),
            'class_name' => $class_name,

        ];
    }
}