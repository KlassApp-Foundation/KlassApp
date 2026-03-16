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

class SuspendOrActivateSchool extends Action
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
    try
    {
      foreach ($models as $model)
      {
        $church = \App\Models\School::where('id', $model->id)->first();
        
        if($church->status == '0') 
        {
          $church->status = '1';
        }
        else 
        {
          $church->status = '0';
        }

        $church->save();

        return Action::message('The School Status Updated !');
      }
    }
    catch(Exception $e)
    {
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
