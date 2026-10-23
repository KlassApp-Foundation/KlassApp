<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ChangeAvatarRequest;
use App\Http\Requests\CredentialsUpdateRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Userprofile;
use App\Traits\LogActivity;
use App\Models\Country;
use App\Traits\Common;
use App\Models\User;
use App\Models\City;
use App\Models\WhatsAppUser;
use App\Services\WhatsApp\WhatsAppLinkCodeService;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Exception;
use Hash;

class UserProfileController extends Controller
{
    use Common;
    use LogActivity;

    public function ChangePassword()
    {
        return view('/admin/changepassword');
    }
      
    /**
     * Updates the password of the specified user.
     * 
     * @param \Illuminate\Http\Request $request 
     * 
     * @return \Illuminate\Http\Response
     */
    public function updateChangePassword(ChangePasswordRequest $request)
    {
        $user = User::find(Auth::id());
        $hashedPassword = $user->password;

        if($hashedPassword!='')
        { 
            $user->password = Hash::make($request->newpassword);
            $user->save();

            $ip= $this->getRequestIP();
            $this->doActivityLog(
                $user,
                Auth::user(),
                ['ip' => $ip,'details' => $_SERVER['HTTP_USER_AGENT']],
                LOGNAME_CHANGE_PASSWORD,
                'Changed Profile Password.'                        
            );       
        } 
           
        $res['message']=__('admin_userprofile.password_update');
        return $res;
    }

    public function changeavatar(Request $request)
    {   
        return view('/admin/changeavatar');
    }

     public function getavatar()
    {
        $userprofile = Userprofile::where('user_id', Auth::id())->first();
        $array=[];

        if(Auth::user())
        {
            $array['avatar']=$this->getFilePath($userprofile->avatar);
            $array['id']=$userprofile->id;
        }

        return $array;
    }
 
    /**
     * Updates the avatar image for specified user.
     * 
     * @param \Illuminate\Http\Request $request 
     * 
     * @return \Illuminate\Http\Response
     */
    public function updatechangeavatar(ChangeAvatarRequest $request)
    {
        try
        {
            $userprofile = Userprofile::where('user_id', Auth::id())->first();
           // $path=$this->uploadFile(Auth::user()->school->slug.'/uploads/avatars', $request->avatar);

            $path = \Storage::putFile(Auth::user()->school->slug.'/uploads/avatars', $request->avatar);

            if($path!='')
            {
                $userprofile->avatar = $path ;
                $userprofile->save();  

                $res['message']=__('admin_userprofile.update_avatar');
                $res['avatar']=$this->getFilePath($path);     
            }

            $ip= $this->getRequestIP();
                $this->doActivityLog(
                $userprofile,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
                LOGNAME_CHANGE_AVATAR,
                'Changed Avatar'
            );  
            return $res; 
        }
        catch(Exception $e)
        {
            //dd($e->getMessage());
        }
    }

    public function edit(Request $request)
    {
        $plan = null;
        $planSummary = null;
        $schoolId = Auth::user()->school_id;
        if ($schoolId) {
            $currentPlan = \App\Models\CurrentPlan::with('plan')->where('school_id', $schoolId)->first();
            if ($currentPlan && $currentPlan->plan) {
                $plan = $currentPlan->plan;
            }
            $school = Auth::user()->school;
            if ($school) {
                $planSummary = app(\App\Services\EarlyAdopterOffer::class)->profileLine($school);
            }
        }

        return view('/admin/editprofile', [
            'plan' => $plan,
            'planSummary' => $planSummary,
        ]);
    }

    public function create()
    {
        $country = Country::all(); 
        $city = City::get();
        $userprofile = Userprofile::with('country','city')->where('user_id', Auth::id())->first();

        $array=[];
       
        $array['firstname']=$userprofile->firstname;
        $array['lastname']=$userprofile->lastname;
        $array['birth_firstname']=$userprofile->birth_firstname;
        $array['birth_lastname']=$userprofile->birth_lastname;
        $array['gender']=$userprofile->gender;
        $array['date_of_birth']=\App\Support\DateOfBirth::formatOrNull($userprofile->date_of_birth, 'Y-m-d') ?? '';
        $array['address']=$userprofile->address;
        // $array['country_id']=$userprofile->country->name;
        // $array['city_id']=$userprofile->city->name;
        $array['country_id']=$userprofile->country->id;
        $array['city_id']=$userprofile->city->id;
        $array['pincode']=$userprofile->pincode;
        $array['countrylist']=$country;
        $array['citylist']=$city;

        return $array;      
    }
    
    public function update(Request $request)
    {//dump($request);
        try
        {
            $userprofile = Userprofile::where('user_id', Auth::id())->first();
            
            $userprofile->firstname = $request->firstname;
            $userprofile->lastname = $request->lastname;
            /*$userprofile->birth_firstname = $request->birth_firstname;
            $userprofile->birth_lastname = $request->birth_lastname;*/
            $userprofile->gender = $request->gender;
            $userprofile->date_of_birth = $request->date_of_birth;
            $userprofile->address = $request->address;
            $userprofile->city_id = $request->city_id;
            $userprofile->country_id = $request->country_id;
            $userprofile->pincode = $request->pincode;
            
            $userprofile->save();

            $ip= $this->getRequestIP();
            $this->doActivityLog(
                $userprofile,
                Auth::user(),
                ['ip' => $ip,'details' => $_SERVER['HTTP_USER_AGENT']],
                LOGNAME_EDIT_USERPROFILE,
                'Changed Profile'                        
            );

            $res['message']=__('admin_userprofile.profile_update');
            return $res; 
        }
        catch(Exception $e)
        {
           //dd($e->getMessage());
        } 
    } 


    public function getCredentials($name)
    {
      try
        {  
        $user = User::where([['name',$name],['school_id',Auth::user()->school_id]])->first();
        $array=[];

        if(Auth::user())
        {
            $array['email'] = $user->email==null ? '':$user->email;
            $array['mobile_no']=$user->mobile_no==null ?'':$user->mobile_no;
            $array['usergroup_id']=$user->usergroup_id;
        }

        return $array;
         }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
    }

    public function updateCredentials(CredentialsUpdateRequest $request,$name)
    {
        //dd($name);
        try
        {
        $user = User::where([['name',$name],['usergroup_id',$request->user_group],['school_id',Auth::user()->school_id]])->first();
         $user->tokens()->delete();
        //dd($user);
        $user->email=$request->email;
        $user->mobile_no=$request->mobile_no;
        $user->platform_token  = NULL;
        $user->device_id = NULL;
        $user->save();
    

         $res['message']=__('admin_userprofile.credentials_update');

          $ip= $this->getRequestIP();
            $this->doActivityLog(
                $user,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
                LOGNAME_CHANGE_CREDENTIALS,
                $res['message']
            );  
            return $res; 

         }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            dd($e->getMessage());
        }
    }

    /**
     * Show WhatsApp phone-linking page.
     *
     * Nothing is typed: the page issues a short code, shows a QR and an
     * "Open WhatsApp" link that prefill it, and links the number the message
     * actually arrives from.
     */
    public function phoneLink()
    {
        $user = Auth::user();
        $waUser = WhatsAppUser::where('user_id', Auth::id())
            ->first();

        $link = null;
        $qrSvg = null;
        if (! ($waUser && $waUser->phone)) {
            $link = app(WhatsAppLinkCodeService::class)->issue($user, $this->getRequestIP());
            if (($link['ok'] ?? false) && ! empty($link['url'])) {
                $qrSvg = $this->linkCodeQr((string) $link['url']);
            }
        }

        return view('/admin/whatsapp/phone-link', [
            'waUser' => $waUser,
            'user'   => $user,
            'link'   => $link,
            'qrSvg'  => $qrSvg,
        ]);
    }

    /**
     * Get a fresh link code (the previous one stops working).
     */
    public function issueWhatsAppLinkCode()
    {
        app(WhatsAppLinkCodeService::class)->issue(Auth::user(), $this->getRequestIP());

        return redirect()->route('whatsapp.phone');
    }

    /**
     * Polled by the page while it waits for the message to arrive.
     */
    public function whatsappLinkStatus()
    {
        $status = app(WhatsAppLinkCodeService::class)->status(Auth::user());

        return response()->json([
            'linked'   => (bool) $status['linked'],
            'verified' => (bool) $status['verified'],
        ]);
    }

    /**
     * Unlink the signed-in person's WhatsApp number. Numbers are linked by
     * sending a code from WhatsApp, not by posting a phone number here.
     */
    public function linkWhatsApp(Request $request)
    {
        $request->validate([
            'unlink'   => 'boolean',
        ]);

        $user = Auth::user();

        if ($request->boolean('unlink')) {
            WhatsAppUser::where('user_id', $user->id)
                ->delete();

            $this->doActivityLog(
                $user,
                $user,
                [],
                'unlinkwhatsapp',
                'Unlinked WhatsApp Number'
            );
            return back()->with('success', 'WhatsApp number unlinked successfully.');
        }

        return back();
    }

    /**
     * Inline SVG QR for the wa.me link, the same shape the demo page uses.
     */
    private function linkCodeQr(string $url): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle(220, 2), new SvgImageBackEnd));

        return preg_replace('/^<\?xml[^>]*>\s*/', '', $writer->writeString($url)) ?? '';
    }
}
