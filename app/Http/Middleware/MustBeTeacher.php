<?php

namespace App\Http\Middleware;

use App\Services\ExamAuthorization;
use Closure;
use Illuminate\Http\Request;

class MustBeTeacher
{
    /**
     * Marks entry, save, submit, and the links on that screen.
     * Every other teacher route stays teacher-only.
     *
     * @var list<string>
     */
    private const MARKS_ENTRY_ROUTES = [
        'teacher.exam.marks',
        'teacher.exam.marks.enter',
        'teacher.exam.marks.save',
        'teacher.exam.marks.submit',
        'teacher.exam.marks.view',
        'teacher.exam.marks.template',
        'teacher.exam.marks.import.page',
        'teacher.exam.marks.import.preview',
        'teacher.exam.marks.import.confirm',
        'teacher.exam.marks.import.result',
        'teacher.student.marks.edit',
        'teacher.marks.update',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $group = (int) \Auth::user()->usergroup_id;

        if ($group === 5) {
            return $next($request);
        }

        if (in_array($group, ExamAuthorization::MARKS_MANAGER_GROUPS, true) && $this->isMarksEntry($request)) {
            return $next($request);
        }

        if ($group === 1) {
            return redirect('/superadmin/dashboard');
        }

        if ($group === 3) {
            return redirect('/admin/dashboard');
        }

        if ($group === 6) {
            return redirect('/student/dashboard');
        }

        if ($group === 8) {
            return redirect('/library/dashboard');
        }

        abort(404);
    }

    private function isMarksEntry(Request $request): bool
    {
        $name = $request->route()?->getName();

        return is_string($name) && in_array($name, self::MARKS_ENTRY_ROUTES, true);
    }
}
