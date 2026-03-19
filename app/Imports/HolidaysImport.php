<?php
   
namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithHeadingRow;  
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use App\Helpers\SiteHelper;
use App\Models\Events;
use Exception;
    
class HolidaysImport implements ToCollection , WithHeadingRow
{
  public function collection(Collection $rows)
  {
    try 
    {
      $school_id = Auth::user()->school_id;
      $academic_year = SiteHelper::getAcademicYear($school_id);

      foreach ($rows as $row) 
      { 
        $event = Events::where([
            ['school_id',$school_id],
            ['academic_year_id',$academic_year->id],
            ['select_type','school'],
            ['category','holidays'],
            ['title',$row['title']],
            ['start_date',date('Y-m-d H:i:s',strtotime($row['date']))]
        ])->first();
        if(!$event)
        {
          $holiday = new Events;

          $holiday->school_id         = $school_id;
          $holiday->academic_year_id  = $academic_year->id;
          $holiday->select_type       = 'school';
          $holiday->title             = $row['title'];
          $holiday->category          = 'holidays';
          $holiday->start_date        = date('Y-m-d',strtotime($row['date']));
          $holiday->end_date          = date('Y-m-d',strtotime($row['date']));

          $holiday->save();

          $insertedcount++;  
        }
      } 
      \Session::put('insertedcount',$insertedcount);       
    }
    catch(Exception $e)
    {
      //dd($e->getMessage());
    }
  }
}
