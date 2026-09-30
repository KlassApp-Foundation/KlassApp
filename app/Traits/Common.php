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
        // Null/empty avatars are common; Storage::url() TypeErrors on null and
        // that escapes the old Exception-only catch (TypeError extends Error).
        if ($file === null || $file === '') {
            return '';
        }

        try
        {
            return $this->fileUrlForStoredFile($file);
        }
        catch(\Throwable $e)
        {
            Log::info($e->getMessage());
        }

        return '';
    }

    /**
     * Resolve a display URL for a stored file on the given (or default) disk.
     *
     * Local disks serve regular /storage URLs. Laravel Cloud object storage
     * buckets are private, so when the disk has no public base URL configured
     * we hand out a short lived signed URL instead (R2 presigned GET); public
     * buckets configured with a url are served directly.
     *
     * Two kinds of path never reach the disk: absolute URLs (social avatars
     * already are display URLs) and bundled assets that ship in public/ —
     * #892 moved the default disk to an object-storage bucket where those
     * files do not exist, so resolving them there signs a URL to a missing
     * object and every default avatar breaks.
     */
    public function fileUrlForStoredFile($file, $disk = null)
    {
        $file = (string) $file;

        if (preg_match('#^https?://#i', $file) === 1) {
            return $file;
        }

        if (is_file(public_path($file))) {
            return asset($file);
        }

        if ($file === 'uploads/images.jpg') {
            return asset('uploads/user/avatar/default-user.jpg');
        }

        $disk = $disk ?: config('filesystems.default');
        $storage = \Storage::disk($disk);

        if (config("filesystems.disks.$disk.driver") === 's3') {
            return config("filesystems.disks.$disk.url")
                ? $storage->url($file)
                : $storage->temporaryUrl($file, now()->addMinutes(30));
        }

        return $storage->url($file);
    }

    public function uploadFile($folder,$file)
    {
        $path = '';

        try
        {
            // No per-object visibility: Cloudflare R2 rejects ACL headers;
            // bucket level visibility governs access.
            $path = \Storage::putFile($folder, $file);
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
            $path = \Storage::put($folder, $contents);
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


    public function getFilePathforDownload($file, $disk='')
    { 
        $path = '';
        try
        {
            $storage = $disk !== '' ? \Storage::disk($disk) : \Storage::disk();
            $path = $storage->get($file);
        }
        catch(\Throwable $e)
        {
            Log::info($e->getMessage());
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