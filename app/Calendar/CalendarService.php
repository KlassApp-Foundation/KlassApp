<?php

namespace App\Calendar;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use App\Events\StandardPushEvent;
use App\Events\CalendarEvent;
use App\Events\PushEvent;
use App\Models\Events;


class CalendarService {

	public function test() {
		return "this works";
	}

	public function events($school_id,$academic_year)
	{
		 $events         =   Events::where([['school_id',$school_id],['academic_year_id',$academic_year]])->get();
		 return $events;
	}

	public function eventscount($school_id,$academic_year)
	{
		 $events          =   Events::where([['school_id',$school_id],['academic_year_id',$academic_year],['category','!=','holidays']])->get();
		 return $events;

	}


	public function createEvent($school_id,$academic_year,$request)
	{
		//dd($request->title);

		$events=Events::create($request);
        $executed_at  =  date('Y-m-d', strtotime('-2 days', strtotime($events->start_date)));

        /*$this->sendToReminderEvent($events,$executed_at,'first');
          if(env('MAIL_STATUS') == 'on')
          {
            event(new CalendarEvent($events));
          }*/

        if($events->select_type=='class')
        {
          $data=[];

          $data['school_id']=$school_id;
          $data['standard_id']=$events->standard_id;
          $data['message']='New Event created';
          $data['type']='event';

          event(new StandardPushEvent($data));
        }
        else
        {
          $data=[];

          $data['school_id']=$school_id;
          $data['message']='New Event created';
          $data['type']='event';

          event(new PushEvent($data));
        }

        return $events;
       

	}

	public function getEvent($events_id,$school_id)
	{
		$event = Events::where([['id',$events_id],['school_id',$school_id]])->first();
		return $event;

	}


	public function updateEvent($event_id,$school_id,$academic_year,$request)
	{

		$events = Events::where('id',$event_id)->first();

        /*if(Input::hasFile('image'))
        {
          $file = $request->file('image');
          //$name = $file->getClientOriginalName();
          $path = $this->uploadFile('uploads/admin/event/image',$file);
          $events->image = $path;  
        }
        else
        {
          $events->image = $events->image;
        }*/

        $events->title       = $request->title;
        $events->description = $request->description;
        $events->repeats     = $request->repeats;
        if($request->select_type=='class')
        {
          $events->standard_id     = $request->standard_id;
        }
      
        $events->freq        = $request->freq;
        $events->freq_term   = $request->freq_term;
        $events->location    = $request->location;
        $events->category    = $request->category;
        $events->organised_by= $request->organised_by;
        $events->start_date  = date('Y-m-d H:i:s',strtotime($request->start_date));
        $events->end_date    = date('Y-m-d H:i:s',strtotime($request->end_date));
               
        $events->save();

        if($request->select_type=='class')
        {
          $data=[];

          $data['school_id']=$school_id;
          $data['standard_id']=$request->standard_id;
          $data['message']='Event updated';
          $data['type']='event';

          event(new StandardPushEvent($data));
        }
        else
        {
          $data=[];

          $data['school_id']=$school_id;
          $data['message']='Event updated';
          $data['type']='event';

          event(new PushEvent($data));
        }  

        return $events;


	}


	public function deleteEvent($id)
	{
		$event = Events::where('id',$id)->first();
       
        $event->delete();

        return $event;


	}



}