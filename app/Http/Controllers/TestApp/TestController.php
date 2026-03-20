<?php

namespace App\Http\Controllers\TestApp;

use App\Http\Controllers\Controller;
use App\Models\Academics\Marks;
use Illuminate\Http\Request;

class TestController extends Controller
{
    // test
    public function testIsStudent(){
        $bad = Marks::where('exam_id', 4)  // or whatever exam id
            ->whereHas('student', fn($q) => $q->where('usergroup_id', '!=', 6))
            ->with('student:id,name,usergroup_id')
            ->get(['student_id']);
      dd($bad);
    }

}
