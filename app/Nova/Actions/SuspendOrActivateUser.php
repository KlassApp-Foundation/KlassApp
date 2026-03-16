<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Laravel\Nova\Actions\Action;
use Illuminate\Support\Collection;
use Laravel\Nova\Fields\ActionFields;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Exception;

class SuspendOrActivateUser extends Action
{
   use InteractsWithQueue, Queueable, SerializesModels;
   /**
    * Perform the action on the given models.
    *
    * @param  \Laravel\Nova\Fields\ActionFields  $fields
    * @param  \Illuminate\Support\Collection  $models
    * @return mixed
    */
   public function handle(ActionFields $fields, Collection $models)
   {
    \DB::beginTransaction();
    try
    {
      foreach ($models as $model)
      {
        $userprofile = \App\Models\Userprofile::with('church')->where('user_id', $model->id)->first();
         if($userprofile->status == 'inactive') 
        {
          $userprofile->status = 'active' ;
        }
        else 
        {
          $userprofile->status = 'inactive';
        }
        $userprofile->save();

        $church = \App\Models\Church::where('id', $userprofile->church_id)->first();
        if($church->status == '0') 
        {
          $church->status = '1';
        }
        else 
        {
          $church->status = '0';
        }
        $church->save();
        \DB::commit();

        return Action::message('The User Status Updated !');
      }
    }
    catch(Exception $e)
    {
      \DB::rollBack();
      //dd($e->getMessage());
    }
   }
   /**
    * Get the fields available on the action.
    *
    * @return array
    */
   public function fields()
   {
       return [];
   }
}
