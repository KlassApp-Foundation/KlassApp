<?php

namespace App\Services\Google\Drive;

use App\Models\AcademicTerm;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\SchoolGoogleConnection;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Services\Google\GoogleApiClient;
use App\Services\Google\GoogleApiException;
use App\Services\Google\GoogleConnectorCatalog;
use App\Services\ReportCardCommentService;
use App\Services\StudentReportCardService;
use App\Services\StudentReportHelperService;

class GoogleDriveService
{
    public function __construct(
        private readonly GoogleApiClient $client,
        private readonly GoogleConnectorCatalog $catalog,
        private readonly StudentReportCardService $reports,
        private readonly StudentReportHelperService $helper,
        private readonly ReportCardCommentService $comments,
    ) {
    }

    /**
     * File one learner's report-card PDF under KlassApp / {term} / {class}.
     *
     * @return array{fileId: string, name: string, webViewLink: string, folder: string}
     */
    public function fileReportCard(User $actor, Exam $exam, int $learnerId): array
    {
        if ((int) $actor->school_id !== (int) $exam->school_id) {
            abort(404);
        }

        $schoolId = (int) $actor->school_id;
        $connection = $this->connection($actor);
        $learner = $this->learner($schoolId, $learnerId);
        $term = AcademicTerm::query()->where('school_id', $schoolId)->whereKey($exam->academic_term_id)->first();
        $section = Section::query()->where('school_id', $schoolId)->whereKey($exam->section_id)->first();
        $stdLink = StandardLink::query()
            ->where('school_id', $schoolId)
            ->where('section_id', $exam->section_id)
            ->where('academic_year_id', $exam->academic_year_id)
            ->where('standard_id', $exam->standard_id)
            ->orderBy('id')
            ->first();

        if ($term === null || $section === null || $stdLink === null) {
            throw new GoogleApiException(404, 'That class is not in this school.', 'class_missing');
        }

        $enrolled = StudentAcademic::query()
            ->where('school_id', $schoolId)
            ->where('user_id', $learner->id)
            ->where('academic_year_id', $exam->academic_year_id)
            ->where('standardLink_id', $stdLink->id)
            ->exists();

        if (! $enrolled) {
            throw new GoogleApiException(404, 'That learner is not in this class.', 'learner_not_in_class');
        }

        $positionMap = $this->reports->computePositionMap($exam, $schoolId);
        $totalLearners = (int) Marks::query()
            ->where('school_id', $schoolId)
            ->where('exam_id', $exam->id)
            ->where('section_id', $exam->section_id)
            ->distinct()
            ->count('student_id');

        $pdf = $this->reports->generatePdf(
            $learner->id,
            $exam,
            $stdLink,
            $schoolId,
            $this->helper,
            $this->comments,
            max(1, $totalLearners),
            $positionMap[$learner->id] ?? 0,
        );

        if (! str_starts_with($pdf, '%PDF')) {
            throw new GoogleApiException(502, 'The report card PDF was empty.');
        }

        $termName = $this->folderName((string) $term->name);
        $sectionName = $this->folderName((string) $section->name);
        $rootId = $this->folder($connection, 'KlassApp', 'root');
        $termId = $this->folder($connection, $termName, $rootId);
        $classId = $this->folder($connection, $sectionName, $termId);
        $filename = $this->filename($learner);

        [$body, $contentType] = $this->multipart($filename, $pdf, $classId);
        $created = $this->client->postBody(
            $connection,
            $this->url('/upload/drive/v3/files'),
            $body,
            $contentType,
            ['uploadType' => 'multipart', 'fields' => 'id,name,webViewLink,parents'],
        );

        $fileId = (string) ($created['id'] ?? '');
        if ($fileId === '') {
            throw new GoogleApiException(502, 'Google Drive did not return a file id.');
        }

        return [
            'fileId' => $fileId,
            'name' => (string) ($created['name'] ?? $filename),
            'webViewLink' => (string) ($created['webViewLink'] ?? ''),
            'folder' => 'KlassApp / '.$termName.' / '.$sectionName,
        ];
    }

    private function connection(User $actor): SchoolGoogleConnection
    {
        $connection = SchoolGoogleConnection::query()->forSchool((int) $actor->school_id)->first();

        if (! $this->catalog->isLive($connection, 'drive')) {
            throw new GoogleApiException(404, 'Google Drive is not connected for this school.', 'not_connected');
        }

        return $connection;
    }

    private function learner(int $schoolId, int $learnerId): User
    {
        $learner = User::query()
            ->where('school_id', $schoolId)
            ->whereKey($learnerId)
            ->where('usergroup_id', 6)
            ->where('status', 'active')
            ->first();

        if ($learner === null) {
            throw new GoogleApiException(404, 'That learner is not an active student in this school.', 'learner_missing');
        }

        return $learner;
    }

    private function folder(SchoolGoogleConnection $connection, string $name, string $parentId): string
    {
        $query = sprintf(
            "mimeType = 'application/vnd.google-apps.folder' and name = '%s' and '%s' in parents and trashed = false",
            $this->escapeQuery($name),
            $this->escapeQuery($parentId),
        );
        $found = $this->client->get($connection, $this->url('/drive/v3/files'), [
            'q' => $query,
            'fields' => 'files(id,name)',
            'pageSize' => 10,
            'spaces' => 'drive',
        ]);

        $existing = $found['files'][0]['id'] ?? null;
        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        $created = $this->client->postJson($connection, $this->url('/drive/v3/files'), [
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentId],
        ]);
        $id = (string) ($created['id'] ?? '');
        if ($id === '') {
            throw new GoogleApiException(502, 'Google Drive did not return a folder id.');
        }

        return $id;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function multipart(string $filename, string $pdf, string $parentId): array
    {
        $boundary = 'klassapp_'.bin2hex(random_bytes(8));
        $meta = json_encode([
            'name' => $filename,
            'mimeType' => 'application/pdf',
            'parents' => [$parentId],
        ], JSON_UNESCAPED_SLASHES);
        $body = "--{$boundary}\r\n"
            ."Content-Type: application/json; charset=UTF-8\r\n\r\n"
            .$meta."\r\n"
            ."--{$boundary}\r\n"
            ."Content-Type: application/pdf\r\n\r\n"
            .$pdf."\r\n"
            ."--{$boundary}--\r\n";

        return [$body, 'multipart/related; boundary='.$boundary];
    }

    private function filename(User $learner): string
    {
        $kls = (string) $learner->registration_number;
        $stem = preg_match('/^KLS\d{7}$/i', $kls) === 1 ? strtoupper($kls) : 'student-'.$learner->id;

        return $stem.'-report-card.pdf';
    }

    private function folderName(string $name): string
    {
        $name = trim((string) preg_replace('/[\r\n\/\\\\]+/', ' ', $name));
        $name = trim((string) preg_replace('/\s+/', ' ', $name));
        if ($name === '') {
            throw new GoogleApiException(422, 'A Drive folder name is missing.');
        }

        return mb_substr($name, 0, 80);
    }

    private function escapeQuery(string $value): string
    {
        return str_replace(['\\', "'"], ['\\\\', "\\'"], $value);
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.google.drive_base_url'), '/').$path;
    }
}
