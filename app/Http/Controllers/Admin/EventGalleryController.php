<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use App\Http\Resources\ShowEventGallery as ShowEventGalleryResource;
use App\Events\Notification\SchoolNotificationEvent;
use App\Http\Requests\EventGalleryRequest;
use Illuminate\Support\Facades\Redirect;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\EventGallery;
use App\Traits\LogActivity;
use App\Events\PushEvent;
use App\Traits\Common;
use Exception;
use Carbon;
use File;

class EventGalleryController extends Controller
{
    use LogActivity;
    use Common;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
       // return view('admin.albums.create');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getPhoto($event_id)
    {
        $event = EventGallery::where([['event_id',$event_id],['school_id',Auth::user()->school_id]])->get();
        return $event;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(EventGalleryRequest $request,$event_id)//EventGallery
    {
      //dump($request);
      try 
      {
        if(count($request->data)!=null)
        {
          for ($i=0;$i<count($request->data);$i++) 
          { 
            $image_parts    = explode(";base64,",$request->data[$i]['path']);
            $image_type_aux = explode("image/",$image_parts[0]);
            $image_type     = $image_type_aux[1];
            $image_base64   = base64_decode($image_parts[1]);
            $school_id      = Auth::user()->school_id;
            $location        = Auth::user()->school->slug.'/photos/events/';
            //$location_path   = public_path().'/'.$location;
                
            $file            =  uniqid() .'.png';

            $db_path=$location.$file;
            //dd($db_path);
            /*if( ! File::isDirectory($location_path)) 
            {
              File::makeDirectory($location_path,0777, true);
            }

            $path= $location_path. $file;
            $img = \File::put($path, $image_base64);*/
            $img = $this->putContents($db_path, $image_base64);

            $create = [
              'event_id'   => $event_id,
              'school_id'  => $school_id,
              'path'       => $db_path,
              'created_by' => Auth::id(),
              'updated_by' => Auth::id(),
            ];
            $photo = EventGallery::create($create); 
            //dump($photo);
          }

          $data = [];

          $data['school_id']    =   $school_id;
          $data['message']      =   'New Event Gallery Created';
          $data['type']         =   'event_gallery';

          event(new PushEvent($data));

          $array = [];

          $array['school_id']   =   $school_id;
          $array['details']     =   trans('notification.event_gallery_add_success_msg');

          event(new SchoolNotificationEvent($array));


          $message=('Events photos added Successfully');
           
          $ip= $this->getRequestIP();
          $this->doActivityLog(
            $photo,
            Auth::user(),
            ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
            LOGNAME_EVENT_PHOTO,
            $message
          ); 

          $res['message']="Uploaded Successfully";
          return $res;
        }
        else
        {
          $res['count']="Select Atleast One";
          return $res;
        }
      }  
      catch (Exception $e) 
      {
        //dd($e->getMessage());
      }       
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($event_id)
    {      
        $event = EventGallery::where([['event_id',$event_id],['school_id',Auth::user()->school_id]])->get();

        return $event;
    }
     
}
