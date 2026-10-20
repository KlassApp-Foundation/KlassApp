<?php

namespace App\Services\Google\Sheets;

use App\Models\Academics\Exam;
use App\Models\User;
use App\Models\UserGoogleConnection;
use App\Services\Google\GoogleApiClient;
use App\Services\Google\GoogleConnectionService;
use App\Services\Google\GoogleApiException;
use App\Services\Google\GoogleConnectorCatalog;
use App\Services\MarksImport\MarksImportPlan;
use App\Services\MarksImport\MarksImportResult;
use App\Services\MarksImport\MarksImportService;

class GoogleSheetsService
{
    public function __construct(
        private readonly GoogleApiClient $client,
        private readonly GoogleConnectorCatalog $catalog,
        private readonly GoogleConnectionService $connections,
        private readonly ClassMarkSheetReader $reader,
        private readonly MarksImportService $imports,
    ) {
    }

    /**
     * @return array{spreadsheetId: string, spreadsheetUrl: string}
     */
    public function export(User $actor, Exam $exam): array
    {
        $this->assertSameSchool($actor, $exam);
        $connection = $this->connection($actor);
        $sheet = $this->reader->forExam($exam, $actor);

        $created = $this->client->postJson($connection, $this->url('/v4/spreadsheets'), [
            'properties' => ['title' => $sheet['title']],
            'sheets' => [
                ['properties' => ['title' => 'Marks']],
                ['properties' => ['title' => 'Exam info']],
            ],
        ]);

        $id = (string) ($created['spreadsheetId'] ?? '');
        if ($id === '') {
            throw new GoogleApiException(502, 'Google Sheets did not return a spreadsheet id.');
        }

        $markValues = array_merge([$sheet['headings']], $sheet['rows']);
        $this->client->putJson(
            $connection,
            $this->url('/v4/spreadsheets/'.$id.'/values/'.rawurlencode('Marks!A1')),
            ['range' => 'Marks!A1', 'majorDimension' => 'ROWS', 'values' => $markValues],
            ['valueInputOption' => 'RAW'],
        );
        $this->client->putJson(
            $connection,
            $this->url('/v4/spreadsheets/'.$id.'/values/'.rawurlencode("'Exam info'!A1")),
            ['range' => "'Exam info'!A1", 'majorDimension' => 'ROWS', 'values' => $sheet['info']],
            ['valueInputOption' => 'RAW'],
        );

        return [
            'spreadsheetId' => $id,
            'spreadsheetUrl' => (string) ($created['spreadsheetUrl'] ?? ''),
        ];
    }

    /**
     * Read a sheet and save marks only through MarksImportService.
     *
     * @return array{committed: bool, plan: MarksImportPlan, result: ?MarksImportResult}
     */
    public function import(User $actor, Exam $exam, string $spreadsheetId, bool $overwrite = false): array
    {
        $this->assertSameSchool($actor, $exam);
        $connection = $this->connection($actor);
        $spreadsheetId = trim($spreadsheetId);
        if ($spreadsheetId === '' || ! preg_match('/^[A-Za-z0-9_-]+$/', $spreadsheetId)) {
            throw new GoogleApiException(422, 'That spreadsheet id is not valid.');
        }

        $marks = $this->client->get($connection, $this->url('/v4/spreadsheets/'.$spreadsheetId.'/values/'.rawurlencode('Marks!A1:Z5000')));
        $info = $this->client->get($connection, $this->url('/v4/spreadsheets/'.$spreadsheetId.'/values/'.rawurlencode("'Exam info'!A1:B50")));

        $plan = $this->imports->previewSheets($exam, $actor, [
            $marks['values'] ?? [],
            $info['values'] ?? [],
        ], 'google-sheet-'.$spreadsheetId);

        if ($plan->isBlocked()) {
            return ['committed' => false, 'plan' => $plan, 'result' => null];
        }

        return [
            'committed' => true,
            'plan' => $plan,
            'result' => $this->imports->commit($plan, $actor, $overwrite),
        ];
    }

    private function connection(User $actor): UserGoogleConnection
    {
        $connection = $this->connections->forUser($actor);

        if (! $this->catalog->isLive($connection, 'sheets')) {
            throw new GoogleApiException(404, 'Google Sheets is not connected for this user.', 'not_connected');
        }

        return $connection;
    }

    private function assertSameSchool(User $actor, Exam $exam): void
    {
        if ((int) $actor->school_id !== (int) $exam->school_id) {
            abort(404);
        }
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.google.sheets_base_url'), '/').$path;
    }
}
