<?php

namespace App\Schoolplus;
use App\Models\User;
use App\Models\Mark;
use App\Models\Exam;
use App\Models\Subject;
use App\Models\ExamSchedule;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\StudentMark as StudentMarkResource;
use App\Helpers\SiteHelper;
use Illuminate\Support\Facades\Auth;
use Exception;
use Log;

class StudentService {

	public function test() {
		return "this works";
	}

	private function getStudentById($studentId) {
		
		$user = User::find($studentId);
		if($user->usergroup_id == 6) {
			return $user;
		}
		throw new ModelNotFoundException('Student not found by ID ' . $studentId);
	}

	public function getBasicInfo($studentId) {
		//return $this->getStudentById($studentId);
	}

	public function getAttendanceInfo($studentId, $period){
		// $period can be today, this week, this month, this acdemic year so far
	}

	public function getClassRoomInfo($studentId){
		// some class room info, Standard, Section, No.of Kids, Class Teacher, Avg. Attendance Rate....
	}

	public function getTeachersList($studentId){
		// list of Teachers associated with this Kid
	}

	public function getSubjectsList($studentId){
		// list of subjects associated with the kid
	}

	public function upcomingClassroomEventsList($studentId){
		// list of upcoming classroom events
	}

	public function pastClassroomEventsList($studentId){
		// list of past upcoming classroom events
	}

	public function getLessonPlanForDay($studentId, $day){
		// get lesson plan for the day- today, yseterday, tomorrow
	}

	public function getMemosOrKudos($studentId, $day){
		// get lesson plan for the day- today, yseterday, tomorrow
	}

	public function getStudentMark($studentId,$examId)
	{
		$academic_year  = SiteHelper::getAcademicYear(Auth::user()->school_id);

		$mark=Mark::where('exam_id',$examId)->where('user_id',$studentId)->where('school_id',Auth::user()->school_id)->where('academic_year_id',$academic_year->id)->get();

		StudentMarkResource::withoutWrapping();

		$mark = StudentMarkResource::collection($mark)->groupBy('exam.name');

		//dump($mark);
		return $mark;
	}

	public function getAllMarks($studentId)
	{
		$academic_year  = SiteHelper::getAcademicYear(Auth::user()->school_id);

		$mark=Mark::where('user_id',$studentId)->where('school_id',Auth::user()->school_id)->where('academic_year_id',$academic_year->id)->get();
		//dd($mark);

		StudentMarkResource::withoutWrapping();

		$mark = StudentMarkResource::collection($mark)->groupBy('exam.name');
//dump($mark);
		return $mark;

	}

	public function compareMarks($studentId,$examIdOne,$examIdTwo,$standardId)
	{
		
		try
        {
		
		$standard=StandardLink::where('id',$standardId)->first();

		$standard_id=$standard->standard_id;
		$section_id=$standard->section_id;
		//dd($standard_id);
		//$subjects=$subjects['name'];
	
		//dd($classCount);
		$subjects=Subject::where('standard_id',$standard_id)->where('section_id',$section_id)->pluck('name')->toArray();
		if(class_exists('Gegok12\Exam\Models\Mark'))
        {
			$marksone=\Gegok12\Exam\Models\Mark::where('user_id',$studentId)->where('exam_id',$examIdOne)->pluck('obtained_marks')->toArray();
			$markstwo=\Gegok12\Exam\Models\Mark::where('user_id',$studentId)->where('exam_id',$examIdTwo)->pluck('obtained_marks')->toArray();

			$examOneAverage=\Gegok12\Exam\Models\Mark::where([['standard_id',$standardId],['exam_id',$examIdOne]])->groupBy('subject_id')->selectRaw('round(avg(obtained_marks)) as avg')->pluck('avg');

			$examTwoAverage=\Gegok12\Exam\Models\Mark::where([['standard_id',$standardId],['exam_id',$examIdTwo]])->groupBy('subject_id')->selectRaw('round(avg(obtained_marks)) as avg')->pluck('avg');
	    }
	    else{
	    	$marksone=Mark::where('user_id',$studentId)->where('exam_id',$examIdOne)->pluck('obtained_marks')->toArray();
			$markstwo=Mark::where('user_id',$studentId)->where('exam_id',$examIdTwo)->pluck('obtained_marks')->toArray();

			$examOneAverage=Mark::where([['standard_id',$standardId],['exam_id',$examIdOne]])->groupBy('subject_id')->selectRaw('round(avg(obtained_marks)) as avg')->pluck('avg');

			$examTwoAverage=Mark::where([['standard_id',$standardId],['exam_id',$examIdTwo]])->groupBy('subject_id')->selectRaw('round(avg(obtained_marks)) as avg')->pluck('avg');
	    }

	    if(class_exists('Gegok12\Exam\Models\Exam'))
        {		

			$examone=\Gegok12\Exam\Models\Exam::where('standard_id',$standardId)->where('id',$examIdOne)->pluck('name')->toArray();

			$examtwo=\Gegok12\Exam\Models\Exam::where('standard_id',$standardId)->where('id',$examIdTwo)->pluck('name')->toArray();
	    }
	    else{
	    	$examone=Exam::where('standard_id',$standardId)->where('id',$examIdOne)->pluck('name')->toArray();

			$examtwo=Exam::where('standard_id',$standardId)->where('id',$examIdTwo)->pluck('name')->toArray();

	    }		
		//dd($examIdTwo);
		
		
		 return view('/admin/exammark/process' , ['subjects'=>$subjects,'marksone'=>$marksone,'markstwo'=>$markstwo,'examone'=>$examone,'examtwo'=>$examtwo,'examOneAverage'=>$examOneAverage,'examTwoAverage'=>$examTwoAverage]);
		 }
        catch(Exception $e)
        {
            dd($e->getMessage());
        }
		
	}



}