<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Traits\LogActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TeacherLinkImportController extends Controller
{
    use LogActivity;

    public function index()
    {
        $schoolId = Auth::user()->school_id;
        $academicYear = AcademicYear::where('school_id', $schoolId)->first();

        $links = Teacherlink::where('school_id', $schoolId)
            ->with(['teacher', 'subject', 'standardLink.section'])
            ->when($academicYear, fn($q) => $q->where('academic_year_id', $academicYear->id))
            ->orderBy('id', 'desc')
            ->paginate(50);

        return view('admin.teacher-links.import', [
            'links' => $links,
            'count' => $links->total(),
        ]);
    }

    public function import(Request $request)
    {
        $schoolId = Auth::user()->school_id;
        $academicYear = AcademicYear::where('school_id', $schoolId)->first();
        if (!$academicYear) {
            return back()->with('error', 'No academic year found. Set one up first.');
        }

        $text = $request->input('data', '');

        // Handle file upload
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $ext = strtolower($file->getClientOriginalExtension());
            $parsable = in_array($ext, ['csv', 'txt', 'xlsx', 'xls']);

            if ($parsable) {
                $rows = $this->parseUploadedFile($file->getRealPath(), $ext);
                $text = '';
                foreach ($rows as $row) {
                    $text .= implode(' | ', $row) . "\n";
                }
            } else {
                return back()->with('error', 'Unsupported file format. Use CSV or XLSX.');
            }
        }

        if (empty(trim($text))) {
            return back()->with('error', 'No data provided. Paste data or upload a file.');
        }

        $lines = array_filter(explode("\n", $text), fn($l) => trim($l) !== '');
        $created = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($lines as $line) {
                $parts = array_map('trim', explode('|', $line));
                if (count($parts) < 3) {
                    $skipped++;
                    continue;
                }

                $teacherName = $parts[0];
                $subjectName = $parts[1];
                $className = $parts[2];
                $phone = $parts[3] ?? '';

                // Find or create teacher
                $teacher = User::where('school_id', $schoolId)
                    ->where('name', $teacherName)
                    ->where('usergroup_id', 5)
                    ->first();

                if (!$teacher) {
                    $teacher = User::create([
                        'school_id' => $schoolId,
                        'usergroup_id' => 5,
                        'name' => $teacherName,
                        'email' => Str::slug($teacherName) . '.' . $schoolId . '@school.edu',
                        'password' => bcrypt('password'),
                        'status' => 'active',
                        'email_verified' => 1,
                        'mobile_no' => $phone ?: null,
                    ]);
                } elseif ($phone && empty($teacher->mobile_no)) {
                    $teacher->update(['mobile_no' => $phone]);
                }

                // Find section (class)
                $section = Section::where('school_id', $schoolId)
                    ->where('name', $className)
                    ->first();

                if (!$section) {
                    $errors[] = "Class '{$className}' not found. Skipped.";
                    $skipped++;
                    continue;
                }

                $standardLink = StandardLink::where('school_id', $schoolId)
                    ->where('section_id', $section->id)
                    ->where('academic_year_id', $academicYear->id)
                    ->first();

                if (!$standardLink) {
                    $errors[] = "No class link found for '{$className}'. Skipped.";
                    $skipped++;
                    continue;
                }

                // Find subject
                $subject = Subject::where('school_id', $schoolId)
                    ->where('section_id', $section->id)
                    ->where('name', $subjectName)
                    ->first();

                if (!$subject) {
                    $errors[] = "Subject '{$subjectName}' not found in {$className}. Skipped.";
                    $skipped++;
                    continue;
                }

                // Create link
                Teacherlink::firstOrCreate([
                    'school_id' => $schoolId,
                    'academic_year_id' => $academicYear->id,
                    'standardLink_id' => $standardLink->id,
                    'subject_id' => $subject->id,
                    'teacher_id' => $teacher->id,
                ]);
                $created++;
            }

            DB::commit();

            $message = "✅ {$created} teacher link(s) created.";
            if ($skipped > 0) $message .= " {$skipped} skipped.";
            if (!empty($errors)) {
                $message .= " Issues: " . implode(' ', array_slice($errors, 0, 3));
                if (count($errors) > 3) $message .= " (+" . (count($errors) - 3) . " more)";
            }

            $this->log("Imported {$created} teacher links", 'teacher-link-import');

            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Database error: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $link = Teacherlink::where('school_id', Auth::user()->school_id)->findOrFail($id);
        $link->delete();
        $this->log("Deleted teacher link #{$id}", 'teacher-link-delete');
        return back()->with('success', 'Teacher link deleted.');
    }

    private function parseUploadedFile(string $path, string $ext): array
    {
        $rows = [];

        if (in_array($ext, ['xlsx', 'xls'])) {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($path);
            $allRows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            // Skip header row
            $rows = array_slice($allRows, 1);
        } elseif ($ext === 'csv' || $ext === 'txt') {
            $handle = fopen($path, 'r');
            $first = true;
            while (($line = fgetcsv($handle)) !== false) {
                if ($first) { $first = false; continue; } // skip header
                $rows[] = $line;
            }
            fclose($handle);
        }

        return $rows;
    }
}
