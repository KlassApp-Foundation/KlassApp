<?php
namespace App\Traits;
use App\Models\Notes;



trait NotesProcess
{
     public function createNotes($note,$school_id,$entity_id,$entity_name,$created_by,$updated_by)
     {

          $notes=new Notes;
          $notes->notes=$note;
          $notes->school_id=$school_id;
          $notes->entity_id=$entity_id;
          $notes->entity_name=$entity_name;
          $notes->created_by=$created_by;
          $notes->updated_by=$updated_by;     
          $notes->save();         
        
     	return $notes;
	}
}