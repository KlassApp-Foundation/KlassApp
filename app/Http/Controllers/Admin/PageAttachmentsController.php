<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use App\Models\ClassRoomPageAttachment;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\ClassRoomPage;
use Illuminate\Http\Request;
use App\Traits\LogActivity;
use App\Helpers\SiteHelper;
use App\Traits\Common;
use Exception;

class PageAttachmentsController extends Controller
{
    use LogActivity;
    use Common;

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request,$page_id)
    {
        //
        try
        {
            $page = ClassRoomPage::where('id',$page_id)->first();

            for($i = 0 ; $i < $request->attachment_count ; $i++)
            {
                $page_attachment = new ClassRoomPageAttachment;

                $page_attachment->page_id = $page_id;
                $page_attachment->status  = 1;

                $attachment = 'attachment_file'.$i;

                $file = $request->file($attachment);
                if($file)
                {
                    $folder =   Auth::user()->school->slug.'/pages/'.$page_id.'/attachments';
                    $path   =   $this->uploadFile($folder,$file);
                    $page_attachment->attachment_file = $path; 
                }   

                $page_attachment->save();

                $page->addMedia($file)->toMediaCollection('page_attachments', env('FILESYSTEM_DRIVER'));
            }

            $message = trans('messages.add_success_msg',[':module' => 'Page Attachment']);

            $ip= $this->getRequestIP();
            $this->doActivityLog(
                $page,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
                LOGNAME_ADD_PAGE_ATTACHMENT,
                $message
            ); 

            $res['success'] = $message;
            return $res;
        }
        catch(Exception $e)
        {
            //dd($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        try
        {
            $page_attachment = ClassRoomPageAttachment::where('id',$id)->first();
            if(Gate::allows('page_attachment',$page_attachment))
            {
                $page_attachment->delete();

                $message=trans('messages.delete_success_msg',['module' => 'Page Attachment']);


                $ip= $this->getRequestIP();
                $this->doActivityLog(
                    $page_attachment,
                    Auth::user(),
                    ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
                    LOGNAME_DELETE_PAGE_ATTACHMENT,
                    $message
                );
                $res['success'] = $message;
                return $res;
                //return redirect()->back()->with('successmessage',$message);
            }
            else
            {
                abort(403);
            }
        }
        catch(Exception $e)
        {
            //dd($e->getMessage());
        }
    }
}