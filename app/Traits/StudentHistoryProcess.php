<?php
/**
 * Trait for processing common
 */
namespace App\Traits;
use Exception;
use Log;
use App\Models\StudentHistory;
use Carbon\Carbon;
/**
 *
 * @class trait
 * Trait for Common Processes
 */
trait StudentHistoryProcess
{
     public static function createReadHistory($school_id,$student_id,$parent_id,$entity_id,$entity_name,$type){
      try{

        $exists=StudentHistory::where([['school_id',$school_id],['student_id',$student_id],['entity_id',$entity_id],['type',$type]])->exists();
        if(!$exists){ 
              $create=[
                'school_id'=>$school_id,
                'student_id'=>$student_id,
                'parent_id'=>$parent_id,
                'entity_id'=>$entity_id,
                'entity_type'=>$entity_name,
                'type'=>$type,
                'read_at'=>Carbon::now(),
              ];
              StudentHistory::create($create);
         }
      }
      catch(Exception $e){
        Log::info($e->getMessage());
      }
     }
     public static function getReadCount($school_id,$entity_id,$entity_name){
         $count=StudentHistory::where([['school_id',$school_id],['entity_id',$entity_id],['entity_name',$entity_name]])->whereNotNull('read_at')->count();
         return $count;
     } 

}