<?php

namespace App\Schoolplus;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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

}