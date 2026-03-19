<?php
/**
 * Trait for processing common
 */
namespace App\Traits;

use App\Models\User;
use Exception;
use Log;

/**
 *
 * @class trait
 * Trait for Common Processes
 */
trait Common
{
    public function getFilePath($file)
    {
        $path = '';

        try
        {
            $path = \Storage::url($file);
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
        return $path;
    }

    public function uploadFile($folder,$file)
    {
        $path = '';

        try
        {
            $path = \Storage::putFile($folder, $file,'public');
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }

        return $path;
    }

    public function getRequestIP()
    {
        $ip = request()->ip();
        try
        {
            if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
        return $ip;
    }

    public function eventImagePath($category,$image)
    {
        $image = '';

        try
        {
            if($category=='exam')
            {
                $image = \Storage::url('uploads/events/exam.png');
            }
            elseif($category=='culturals')
            {
                $image = \Storage::url('uploads/events/culturals.jpg');
            }
            elseif($category=='meeting')
            {
                $image = \Storage::url('uploads/events/meeting.jpg');
            }
            elseif($category=='education')
            {
                $image = \Storage::url('uploads/events/education.jpg');
            }

            return $image;
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }   
    }

    public function putContents($folder,$contents)
    {
        $path = '';

        try
        {
            $path = \Storage::put($folder, $contents,'public');
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }

        return $path;
    }

    public function putContentsByFilename($folder,$contents,$filename)
    {
        $path = '';

        try
        {
            $path = \Storage::putFileAs($folder, $contents,$filename);
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }

        return $path;
    }
    
    public function romanToInteger($roman)
    {
        try
        {
            $result = 0;
            $array = array
            (
                'M'   => 1000,
                'CM'  => 900,
                'D'   => 500,
                'CD'  => 400,
                'C'   => 100,
                'XC'  => 90,
                'L'   => 50,
                'XL'  => 40,
                'X'   => 10,
                'IX'  => 9,
                'V'   => 5,
                'IV'  => 4,
                'I'   => 1
            );
            foreach ($array as $key => $value) 
            {
                while (strpos($roman, $key) === 0) 
                {
                    $result += $value;
                    $roman = substr($roman, strlen($key));
                }
            }
           
            // The Integer should be built, return it
            return $result;
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
    }


    public function getFilePathforDownload($disk='',$file)
    { 
        $path = '';
        try
        {
            if($disk!='')
            { 
                $path = \Storage::disk($disk)->get($file);
            }
            else
            {
                $path = \Storage::get($file);
            }
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
 
        return $path;
    }

    public function unlinkFilePath($file)
    { 
        try
        {

            \Storage::disk('s3')->delete($file);
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
 
        return TRUE;
    }

    public static function is_admin($userid)
    {
        if ($userid == '')
        {
            return FALSE;
        }
        else
        {
            $user = User::where('id', $userid)->first(); 

            if($user->usergroup_id == 3)
            {
                return TRUE;
            }
            return FALSE;
        }
    }
}