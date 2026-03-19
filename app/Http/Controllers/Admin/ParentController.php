<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use App\Http\Resources\ParentDetail as ParentDetailResource;
use App\Http\Resources\ActivityLog as ActivityLogResource;
use App\Http\Resources\Feedback as FeedbackResource;
use App\Http\Resources\Children as ChildrenResource;
use App\Http\Resources\User as UserResource;
use App\Http\Requests\ParentUpdateRequest;
use App\Http\Requests\ParentAddRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Http\Controllers\Controller;
use App\Models\StudentParentLink;
use App\Models\Qualification;
use App\Traits\MemberProcess;
use Illuminate\Http\Request;
use App\Traits\RegisterUser;
use App\Models\Subscription;
use App\Helpers\SiteHelper;
use App\Traits\LogActivity;
use App\Models\Userprofile;
use App\Models\ActivityLog;
use App\Models\Feedback;
use App\Traits\Common;
use App\Models\User;
use Exception;
use Log;

class ParentController extends Controller
{
    use MemberProcess;
    use RegisterUser;
    use LogActivity;
    use Common;
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request)
    {
        //
        /*return Cache::remember('parent_list'.Auth::user()->school_id, env('CACHE_TIME'), function() use($request)  {
            $parents = User::ByRole(7)->where('school_id',Auth::user()->school_id)->get();
            return ParentDetailResource::collection($parents);
        });*/

        return $this->ParentFilter($request,Auth::user()->school_id,7);
    }
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $query = \Request::getQueryString();

        return view('/admin/parent/index',[ 'query' => $query ]);
    }
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        $ref_name = Request('ref_name')?Request('ref_name'):'';
        $count    = User::where('school_id',Auth::user()->school_id)->where('usergroup_id',6)->orWhere('usergroup_id',7)->count();
        $subscription = Subscription::where('school_id',Auth::user()->school_id)->first();

        return view('admin/parent/create' , [ 'ref_name' => $ref_name , 'count' => $count , 'subscription' => $subscription ]);
    }

    public function addList(Request $request)
    {
        $array = [];

        $array['qualificationlist'] = SiteHelper::getQualifications();
        $array['standardLinklist']  = SiteHelper::getStandardLinkList(Auth::user()->school_id);

        if($request->standardLink_id != null)
        {
            $parent = User::where('school_id',Auth::user()->school_id)->ByRole(7)->ByStandardLinkParentList($request->standardLink_id)->get();
            $array['parent'] = UserResource::collection($parent);
        }

        return $array;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function validationParent(ParentAddRequest $request)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ParentAddRequest $request)
    {
        //
        try
        {
            $school_id = Auth::user()->school_id;
            $path = '';
            $student_id = '';
            $user = $this->CreateParent($student_id , $request , $school_id , 7);

            $ip= $this->getRequestIP();
            $this->doActivityLog(
              $user,
              Auth::user(),
              ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
              LOGNAME_ADD_PARENT,
              trans('messages.add_success_msg',['module'=>'Parent'])
            );

            if($request->parent == 'add')
            {
                \Session::put('successmessage',trans('messages.add_success_msg',['module'=>'Parent']));
                return redirect()->back(); 
            }
            else
            {
                $res['success'] = trans('messages.add_success_msg',['module'=>'Parent']);
                return $res;
            }
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($name)
    {
        //
        $user = User::where('name',$name)->first();
        $userprofile = Userprofile::where('user_id',$user->id)->first();
        $parentprofile = $user->getParentDetails();

        return view('/admin/parent/show',[ 'user' => $user , 'userprofile' => $userprofile , 'parentprofile' => $parentprofile ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showChildren($name)
    {
        //
        $parent = User::where('name', $name)->first();
      
        $children = ChildrenResource::collection($parent->children);
         
        return $children;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showFeedbacks($name)
    {
        //
        $parent = User::where('name', $name)->first();

        $conversation = Feedback::where('parent_id',$parent->id)->get();

        $conversation = FeedbackResource::collection($conversation);
         
        return $conversation;
    }

    public function showActivityLog($name)
    {
        //
        $user = User::with('userprofile')->where('name', $name)->first();
        if(Gate::allows('member',$user))
        {
            $activitylog = ActivityLog::where('subject_id',$user->id)->paginate(5);
            $activitylog = ActivityLogResource::collection($activitylog);
         
            return $activitylog;
        }
        else
        {
            abort(403);
        } 
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function editList($name)
    {
        //
        $user = User::where('name',$name)->first();
        $userprofile = Userprofile::where('user_id',$user->id)->first();
        $parentprofile = $user->getParentDetails();
        
        $array = [];

        $array['firstname']         = $userprofile->firstname;
        $array['lastname']          = $userprofile->lastname;
        $array['alternate_no']      = $userprofile->alternate_no == null ? '':$userprofile->alternate_no;
        $array['profession']        = $parentprofile['profession'] == null ? '':$parentprofile['profession'];
        $array['sub_occupation']    = $parentprofile['sub_occupation'] == null ? '':$parentprofile['sub_occupation'];
        $array['designation']       = $parentprofile['designation'] == null ? '':$parentprofile['designation'];
        $array['organization_name'] = $parentprofile['organization_name'] == null ? '':$parentprofile['organization_name'];
        $array['official_address']  = $parentprofile['official_address'] == null ? '':$parentprofile['official_address'];
        $array['annual_income']     = $parentprofile['annual_income'] == null ? '':$parentprofile['annual_income'];
        $array['relation']          = $parentprofile['relation'] == null ? '':$parentprofile['relation'];
        $array['qualification_id']  = $parentprofile['qualification_id'] == null ? '':$parentprofile['qualification_id'];
        $array['qualification_name']= $parentprofile['qualification_name'] == null ? '':$parentprofile['qualification_name'];
        $array['qualificationlist'] = SiteHelper::getQualifications();

        return $array;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($name)
    {
        //
        $ref_name = Request('ref_name')?Request('ref_name'):'';
        $user = User::where('name',$name)->first();
        $userprofile = Userprofile::where('user_id',$user->id)->first();
        $parentprofile = $user->getParentDetails();
        
        return view('/admin/parent/edit',[ 'ref_name' => $ref_name , 'user' => $user , 'userprofile' => $userprofile , 'parentprofile' => $parentprofile ]);
    }

    public function editValidationUser(ParentUpdateRequest $request,$name)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(ParentUpdateRequest $request, $name)
    {
        //
        try
        { 
            $user = User::where('name',$name)->first();

            $school_id = Auth::user()->school_id;

            $userprofile = $this->UpdateParent($student_id = '' , $request , $school_id , $user->id);

            $ip= $this->getRequestIP();
            $this->doActivityLog(
                $userprofile,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
                LOGNAME_EDIT_PARENT,
                trans('messages.update_success_msg',['module'=>'Parent'])
            );

            \Session::put('successmessage',trans('messages.update_success_msg',['module'=>'Parent']));
            return redirect()->back(); 
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
    }

    public function destroy($name)
    {
        \DB::beginTransaction();
        try
        {
            $user = User::where('name',$name)->first();
            
            // $studentparentlink = StudentParentLink::where('parent_id',$user->id);
             $studentparentlink = StudentParentLink::where('parent_id',$user->id)->first();

            // if(count($studentparentlink)>0)
             if($studentparentlink)
            {
                $studentparentlink->delete();
            }


            $userprofile = Userprofile::where('user_id',$user->id)->first();

            $userprofile->delete();

            $user->delete();

            $message=trans('messages.delete_success_msg',['module' => 'Parent']);

            $ip= $this->getRequestIP();
            $this->doActivityLog(
                $user,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
                LOGNAME_DELETE_PARENT,
                $message
            ); 
            \Session::put('successmessage',$message);
            \DB::commit();
            return redirect('/admin/parents');
        }
        catch(Exception $e)
        {
            \DB::rollBack();
            //dd($e->getMessage());
        } 
    }

}