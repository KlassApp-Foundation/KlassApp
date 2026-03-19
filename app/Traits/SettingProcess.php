<?php
namespace App\Traits;
use App\Models\Setting;

trait SettingProcess
{
  
     public function updatesettings($key,$value)
     {
     	//dd($key);
     	
        $user=Setting::where('key',$key)->first();
        $user->value=$value;
        $user->save();

        //dd($value);
        return TRUE;
}
}