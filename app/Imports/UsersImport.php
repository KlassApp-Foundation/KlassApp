<?php
   
namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithHeadingRow;  
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use App\Models\Subscription;
use App\Models\StandardLink;
use App\Models\Qualification;
use App\Models\AcademicYear;
use App\Traits\RegisterUser;
use App\Models\Standard;
use App\Models\Section;
use App\Models\Country;
use App\Traits\Common;
use App\Models\State;
use App\Models\City;
use App\Models\User;
use Carbon\Carbon;
use Exception;
    
class UsersImport implements ToCollection , WithHeadingRow
{
    use RegisterUser;
    use Common;

    public function collection(Collection $rows)
    {
      try 
      {
        $school_id      = Auth::user()->school_id;
        $academic_year  = AcademicYear::where('school_id',$school_id)->where('status',1)->first();
        //dd($school_id);
        $user_count     = User::ByRole(6)->where('school_id',$school_id)->count();
        $subscription   = Subscription::where('school_id',$school_id)->first();
        $count          = $subscription->plan->no_of_members - $user_count;

       /* if( (count($rows)>0) && (count($rows)<=($count)) )
        {*/
          foreach ($rows as $row) 
          { 
            $array = str_getcsv($row['parent_qualification']);
            for($i = 0; $i < count($array) ; $i++)
            {
              $arr[$i] = Qualification::where('type','ug')->where('type','pg')->where('display_name','LIKE','%'.$array[$i].'%')->pluck('id')->toArray();
              $qualification_array[$i] = implode('', $arr[$i]);
              if($qualification_array[$i] == null)
              {
                $qualification_id[$i] = 1;
              }
            }

            if(is_int($row['class']))
            {
              $class_name = $row['class'];
            }
            else
            {
              if( ($row['class'] == 'LKG') || ($row['class'] == 'UKG') )
              {
                $class_name = $row['class'];
              }
              else
              {
                $class_name   = $this->romanToInteger($row['class']);
              }
            }

            $country      = Country::where('name','LIKE','%'.$row['country'].'%')->first();
            $state        = State::where('name','LIKE','%'.$row['state'].'%')->first();
            $city         = City::where('name','LIKE','%'.$row['city'].'%')->first();
            $standard     = Standard::where([['school_id',$school_id],['name','LIKE',$class_name]])->first();
            $section      = Section::where([['school_id',$school_id],['name','LIKE',$row['section']]])->first();
            $standardLink = StandardLink::where([['school_id',Auth::user()->school_id],['standard_id',$standard->id],['section_id',$section->id]])->first();

            $student->firstname                 =  $row['firstname'];
            $student->lastname                  =  $row['lastname'];
            $student->mobile_no                 =  $row['mobile_no'];
            $student->email                     =  $row['email'] == '' ? null:strtolower($row['email']);
            $student->gender                    =  strtolower($row['gender']); 
            $student->date_of_birth             =  date('Y-m-d',strtotime($row['date_of_birth']));
            $student->blood_group               =  $row['blood_group'] == '' ? null:str_replace('ve', '', strtolower($row['blood_group']));
            $student->standard                  =  $standardLink->id; 
            $student->address                   =  $row['address'];
            $student->city_id                   =  $city->id;
            $student->state_id                  =  $state->id; 
            $student->country_id                =  $country->id;
            $student->pincode                   =  $row['pincode'];
            $student->birth_place               =  $row['birth_place'];
            $student->native_place              =  $row['native_place'];
            $student->mother_tongue             =  $row['mother_tongue'];
            $student->caste                     =  $row['caste'];
            $student->sub_caste                 =  $row['sub_caste'];
            $student->aadhar_number             =  $row['aadhar_number'];
            $student->joining_date              =  date('Y-m-d',strtotime($row['joining_date']));
            $student->registration_number       =  $row['admission_number'];
            $student->EMIS_number               =  $row['emis_number'];
            $student->roll_number               =  $row['roll_number'];
            $student->id_card_number            =  $row['id_card_number'];
            if( ($class_name == 10) || ($class_name == 12) )
            {
              $student->board_registration_number =  $row['board_registration_number'];
            }
            else
            {
              $student->board_registration_number =  '';
            }
            $student->mode_of_transport         = $row['mode_of_transport'];
            $student->driver_name               = $row['driver_name'];
            $student->driver_contact_number     = $row['driver_contact_number'];
            $student->siblings                  = $row['siblings'];
            $student->siblings_count            = $row['siblings_count'];
            $student->sibling_relation          = str_getcsv($row['sibling_relation']);
            $student->sibling_name              = str_getcsv($row['sibling_name']);
            $student->sibling_date_of_birth     = str_getcsv($row['sibling_date_of_birth']);
            $student->sibling_standard          = str_getcsv($row['sibling_class']);
            $student->notes                     = $row['notes'];

            $parent_status     = User::where([['school_id',$school_id],['mobile_no',$row['parent_mobile_no']],['usergroup_id',7]])->first();
            if(count($parent_status)<=0){
            $parent->parent             = 'add';
            $parent->firstname          = $row['parent_firstname'];
            $parent->lastname           = $row['parent_lastname'];
            $parent->mobile_no          = $row['parent_mobile_no'];
            $parent->alternate_no       = $row['parent_alternate_no'];
            $parent->qualification_id   = $qualification_id;
            $parent->email              = $row['parent_email'];
            $parent->profession         = $row['parent_occupation'];
            $parent->designation        = $row['parent_designation'];
            $parent->sub_occupation     = $row['parent_sub_occupation'];
            $parent->organization_name  = $row['parent_organization_name'];
            $parent->official_address   = $row['parent_official_address'];
            $parent->annual_income      = $row['parent_annual_income'];
            $parent->relation           = $row['relation'];
            }
            else
            {
               $parent->parent             = 'select';
               $parent->select_id          = $parent_status->id;
            }

            $mobile_no  = $row['parent_mobile_no'];
            $registration_number = $row['admission_number'];
            $avatar = '';
            /*$user=User::where('mobile_no',$mobile_no)->orWhereHas('userprofile',function($query) use($registration_number)
              { 
                $query->where('registration_number',$registration_number);
              })->exists();
            if(!$user)
            {*/ // hidden for demo
              $student = $this->CreateUser($student,$school_id,$academic_year->id,$avatar,6);
              $this->CreateParent($student->id,$parent,$school_id,7);
              $insertedcount++;   
            //}          
          } 
          \Session::put('insertedcount',$insertedcount);      
        // }
        // else
        // {
        //   \Session::put('count',$count);
        // }
      }
      catch(Exception $e)
      {
        //dd($e->getMessage());
      }
    }
}