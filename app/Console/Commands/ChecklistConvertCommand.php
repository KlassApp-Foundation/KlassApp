<?php

namespace App\Console\Commands;

use App\Services\Onboarding\ChecklistWorkbookConverter;
use Illuminate\Console\Command;

/**
 * T-079 groundwork. Converts a filled onboarding checklist workbook (.xlsx) into the files the
 * existing importers take, plus report.md. Reads the file, writes only to the output folder, and
 * never touches the database.
 */
class ChecklistConvertCommand extends Command
{
    protected $signature = 'checklist:convert
        {file : Path to the checklist workbook (.xlsx)}
        {--out= : Output folder (default: ./checklist-output next to the current directory)}
        {--force : Write into a folder that already has files}';

    protected $description = 'Convert a filled onboarding checklist workbook into importer files and a validation report (no database writes)';

    public function handle(ChecklistWorkbookConverter $converter): int
    {
        $file = (string) $this->argument('file');
        if (! is_file($file) || ! is_readable($file)) {
            $this->error("Cannot read {$file}.");

            return self::FAILURE;
        }

        try {
            $result = $converter->convert($file);
        } catch (\Throwable $e) {
            $this->error('Could not read the workbook: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($result['blocked'] !== null) {
            $this->error('Refusing to write import files. '.$result['blocked']);

            return self::FAILURE;
        }

        $out = rtrim((string) ($this->option('out') ?: getcwd().'/checklist-output'), '/');
        if (is_dir($out) && count(scandir($out) ?: []) > 2 && ! $this->option('force')) {
            $this->error("{$out} is not empty. Pass --force to write into it.");

            return self::FAILURE;
        }

        foreach (['', '/no-importer', '/pending'] as $sub) {
            if (! is_dir($out.$sub) && ! mkdir($out.$sub, 0775, true) && ! is_dir($out.$sub)) {
                $this->error("Cannot create {$out}{$sub}.");

                return self::FAILURE;
            }
        }

        foreach ($result['files'] as $name => $rows) {
            $this->csv("{$out}/{$name}", array_merge([ChecklistWorkbookConverter::FILES[$name]], $rows));
        }
        foreach ($result['noImporter'] as $name => $rows) {
            $this->csv("{$out}/no-importer/{$name}", $rows);
        }
        foreach ($result['pending'] as $name => $rows) {
            if (str_ends_with($name, '.txt')) {
                file_put_contents("{$out}/pending/{$name}", $rows[0][0]."\n");
            } else {
                $this->csv("{$out}/pending/{$name}", $rows);
            }
        }
        file_put_contents("{$out}/report.md", $converter->report($result, basename($file)));

        $errors = count(array_filter($result['flags'], fn ($f) => $f['severity'] === 'error'));
        $this->info("Wrote files to {$out}. {$errors} flagged item(s); see report.md.");

        return self::SUCCESS;
    }

    /** @param  list<list<mixed>>  $rows */
    private function csv(string $path, array $rows): void
    {
        $h = fopen($path, 'w');
        foreach ($rows as $row) {
            // Guard against spreadsheet formula injection when the CSV is opened in Excel.
            fputcsv($h, array_map(fn ($v) => is_string($v) && preg_match('/^[=@\t\r]/', $v) ? "'".$v : $v, $row), ',', '"', '\\');
        }
        fclose($h);
    }
}
