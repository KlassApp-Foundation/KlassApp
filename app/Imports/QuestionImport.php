<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Auth;
use App\Models\QuizQuestion;
use App\Models\QuizOption;
use Exception;
use Log;

class QuestionImport implements ToCollection,WithHeadingRow
{
    /**
    * @param Collection $collection
    */

    public $chapter_id;
    public $head_id;

    public function  __construct($chapter_id,$head_id)
	{
	    $this->chapter_id =$chapter_id;
	    $this->head_id =$head_id;
	}

    public function collection(Collection $collection)
    {
        //
        \DB::beginTransaction();
        try 
      {
      
     // dd($collection);
      foreach ($collection as $row) 
      { 
        
        
          $question                     = new QuizQuestion;
          $question->chapter_id         = $this->chapter_id;
          $question->head_id            = $this->head_id;
          $question->question           = $row['question'];
          $question->type               = $row['type'];
          $question->page_no            = $row['page_no'];
          $question->created_by         = Auth::id();
          $question->save();

         // dd($question);

          $options=array('option1' => $row['option1'],'option2' => $row['option2'],'option3' => $row['option3'],'option4' => $row['option4'] );

          //dd($options);

           foreach ($options as $key => $option_value) {
            	if($option_value!=null && $option_value!=''){
                $option = new QuizOption;
                $option->option     = $option_value;
                $option->is_answer  = 0;
                $option->question_id= $question->id;
                $option->save();
              }
            } 
            
          $insertedcount++;  
    
      } 

        \DB::commit();

      \Session::put('questioncount',$insertedcount);       
    }
    catch(Exception $e)
    {
    	 \DB::rollBack();
      dd($e->getMessage());
    }

    }
}
