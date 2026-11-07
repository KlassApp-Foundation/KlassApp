<?php

namespace Tests\Feature\Onboarding\Steps;

use App\Models\Country;
use App\Models\Plan;
use App\Models\School;
use App\Models\User;
use App\Onboarding\OnboardingProgress;
use App\Onboarding\Steps\StepIssueCatalog;
use App\Onboarding\Steps\StepRegistry;
use App\Onboarding\Steps\StepSchema;
use App\Onboarding\Steps\StepSchemaCatalog;
use App\Services\OnboardingStepsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PlaybookSyncTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private Plan $plan;

    private StepRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'status' => 1,
            'order' => 1,
        ]);

        $this->school = School::create([
            'name' => 'Playbook School',
            'email' => 'playbook@test.sch.ug',
            'phone' => '0700000088',
            'slug' => 'playbook-school',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 0,
        ]);

        User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Playbook Admin',
            'email' => 'admin@playbook-school.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        $this->plan = Plan::create([
            'name' => 'Freemium',
            'display_name' => 'Freemium',
            'cycle' => 30,
            'no_of_students' => 0,
            'no_of_users' => 0,
            'amount' => 0,
            'order' => 1,
            'is_active' => 1,
        ]);

        $this->registry = app(StepRegistry::class);
    }

    public function test_playbook_steps_match_the_registry_in_order(): void
    {
        $markdown = $this->playbook();
        preg_match_all('/^## step: (\S+)\s*$/m', $markdown, $headings);
        $registryKeys = array_map(fn ($step) => $step->key(), $this->registry->all());

        $this->assertSame($registryKeys, $headings[1]);
        $this->assertStringContainsString('Exams and SchoolPay are not part of onboarding.', $markdown);
        $this->assertStringContainsString('That card is not in the setup driver yet.', $markdown);
        $this->assertStringContainsString('one line per finished step', $markdown);
        $this->assertStringNotContainsString('The school confirms before the engine writes.', $markdown);
        $this->assertStringNotContainsString('Nothing is saved on a blank confirmation.', $markdown);
        $this->assertSame($registryKeys, array_keys(OnboardingProgress::summaryTokens()));
        $this->assertGreaterThan(
            strrpos($markdown, '## step:'),
            strpos($markdown, '## When setup is finished'),
        );

        $finished = trim($this->headingBody($markdown, '## When setup is finished'));
        $this->assertNotSame('', $finished);
        $this->assertStringContainsString('invite parents', strtolower($finished));

        foreach (StepSchemaCatalog::EXCLUDED_KEYS as $banned) {
            $this->assertDoesNotMatchRegularExpression(
                '/^## step: '.preg_quote($banned, '/').'\s*$/m',
                $markdown,
            );
            $this->assertNotContains($banned, $registryKeys);
            $this->assertArrayNotHasKey($banned, OnboardingProgress::summaryTokens());
        }
    }

    public function test_playbook_describes_each_step_the_way_the_schema_does(): void
    {
        $sections = $this->stepSections($this->playbook());

        foreach ($this->registry->all() as $step) {
            $key = $step->key();
            $section = $sections[$key];
            $schema = $step->schema($this->school);

            $documented = $this->field($section, 'Question');
            if ($key === 'country') {
                // docs/onboarding/playbook.md is owned by #1237 and still asks
                // the short question. The default country is added by this
                // service (CountryStep / OnboardingStepsService). Toshi calls
                // the service and does not set the country itself.
                $this->assertSame('Which country is your school in?', $documented, $key);
                $this->assertStringStartsWith($documented, $step->question(), $key);
                $this->assertStringContainsString(
                    OnboardingStepsService::DEFAULT_COUNTRY.' is selected for now',
                    $step->question(),
                    $key,
                );
            } else {
                $this->assertSame($step->question(), $documented, $key);
            }
            $this->assertSame($schema->actor->school, $this->field($section, 'The school'), $key);
            $this->assertSame($schema->actor->engine, $this->field($section, 'The engine'), $key);

            $needed = $this->field($section, "What you'll need");
            $this->assertNotSame('', $needed, $key);
            foreach ($this->requiredLabels($schema) as $label) {
                $this->assertStringContainsString($label, $needed, $key.' '.$label);
            }
            foreach ($this->extensions($schema) as $extension) {
                $this->assertStringContainsString($extension, $needed, $key.' '.$extension);
            }

            $this->assertButtons($key, $section, $schema);
            $this->assertChoices($key, $section, $schema);
            $this->assertDefaults($key, $this->field($section, 'Default'), $schema->defaults);

            $confirmation = $this->field($section, 'Confirmation');
            if ($schema->confirm) {
                $this->assertNotSame('', $confirmation, $key);
            } else {
                $this->assertSame('none', strtolower($confirmation), $key);
            }
            $summary = $this->field($section, 'Summary');
            $this->assertSame($summary, OnboardingProgress::template($key), $key);
            $this->assertSame(OnboardingProgress::summaryTokens()[$key], $this->braceTokens($summary), $key);

            if (in_array($key, OnboardingStepsService::OPTIONAL_STEPS, true)) {
                $this->assertStringContainsString(OnboardingProgress::SKIPPED_FOR_LATER, $section, $key);
            }
            if (in_array($key, OnboardingStepsService::FINISH_WITHOUT, true)) {
                $this->assertStringContainsString('The summary line is left out until the code matches.', $section, $key);
            }
            if ($key === 'uneb_center') {
                $this->assertStringContainsString(OnboardingProgress::LEFT_BLANK, $section, $key);
            }
            if ($key === 'school_category') {
                $this->assertStringContainsString(OnboardingProgress::ALREADY_SET_UP, $section, $key);
            }
        }
    }

    public function test_playbook_lists_every_issue_code_with_its_cause_and_fix(): void
    {
        $sections = $this->stepSections($this->playbook());
        $codes = StepIssueCatalog::codes();
        $fallback = StepIssueCatalog::genericCodeRule()['fallback'];

        $this->assertSame([], array_diff(array_keys($codes), array_keys($sections)));

        foreach ($sections as $key => $section) {
            $rows = $this->issueRows($this->issuesBlock($section));
            $documented = [];

            foreach ($rows as $row) {
                $this->assertNotSame('', $row['cause'], $row['code']);
                $this->assertNotSame('', $row['fix'], $row['code']);
                $this->assertCatalogueRow($key, $row['code'], $row['cause'], $row['fix']);
                $documented[] = $row['code'];
            }

            foreach ($fallback as $suffix) {
                $this->assertNotContains($key.'.'.$suffix, $documented, $key);
            }

            $this->assertEqualsCanonicalizing(
                $codes[$key] ?? [],
                array_values(array_unique($documented)),
                $key,
            );
        }

        foreach ($codes as $key => $stepCodes) {
            foreach ($stepCodes as $code) {
                $this->assertSame($key, StepIssueCatalog::stepKeyForCode($code), $code);
            }
        }

        $this->assertSame(
            ['whatsapp' => 'whatsapp_verify', 'plan' => 'plan_selection'],
            StepIssueCatalog::prefixSteps(),
        );
    }

    public function test_generic_codes_are_a_rule_and_fallbacks_stay_out_of_the_step_tables(): void
    {
        $markdown = $this->playbook();
        $generic = $this->headingBody($markdown, '## Generic issue codes');
        $rule = StepIssueCatalog::genericCodeRule();

        $this->assertLessThan(
            strpos($markdown, '## step:'),
            strpos($markdown, '## Generic issue codes'),
        );

        foreach ($rule['steps'] as $step) {
            $this->assertStringContainsString('`'.$step.'`', $generic, $step);
        }

        $rows = $this->issueRows($generic);
        $codes = array_column($rows, 'code');
        foreach (array_merge($rule['suffixes'], $rule['fallback']) as $suffix) {
            $this->assertContains('{key}.'.$suffix, $codes, $suffix);
        }

        foreach ($rows as $row) {
            $suffix = substr($row['code'], strlen('{key}.'));
            $this->assertSame('{key}.'.$suffix, $row['code']);

            if (in_array($suffix, $rule['suffixes'], true)) {
                $issue = StepIssueCatalog::fromMessage($rule['steps'][0], $row['cause']);
                $this->assertSame($rule['steps'][0].'.'.$suffix, $issue->code, $row['code']);
                $this->assertSame($row['fix'], $issue->fix, $row['code']);
            }

            if ($suffix === 'rejected') {
                $issue = StepIssueCatalog::fromMessage('school_name', $row['cause']);
                $this->assertSame('school_name.rejected', $issue->code);
                $this->assertSame($row['fix'], $issue->fix);
            }

            if ($suffix === 'save') {
                $issue = StepIssueCatalog::fromValidation('school_name', ValidationException::withMessages([
                    'name' => $row['cause'],
                ]));
                $this->assertSame('school_name.save', $issue->code);
                $this->assertSame($row['fix'], $issue->fix);
            }
        }

        $this->assertStringContainsString('Check this answer and try again.', $generic);
        $this->assertStringContainsString('whatsapp_verify', $generic);
        $this->assertStringContainsString('plan_selection', $generic);

        $empty = StepIssueCatalog::fromValidation('emis', ValidationException::withMessages([
            'ministry_code' => '',
        ]));
        $this->assertSame('emis.save', $empty->code);
        $this->assertSame('Check this answer and try again.', $empty->fix);

        $unknown = StepIssueCatalog::fromMessage('fees', 'This message is not catalogued.');
        $this->assertSame('fees.rejected', $unknown->code);
        $this->assertSame('Correct the value and try again.', $unknown->fix);
        $this->assertArrayNotHasKey('fees.rejected', array_flip(StepIssueCatalog::codes()['fees'] ?? []));
    }

    private function assertButtons(string $key, string $section, StepSchema $schema): void
    {
        $kind = $schema->validation['kind'] ?? '';

        if ($kind === 'one_of') {
            $parsed = $this->bulletOptions($section, 'Buttons');
            $this->assertSame($schema->optionValues(), array_column($parsed, 'value'), $key);
            foreach ($parsed as $index => $row) {
                $this->assertSame($schema->options[$index]['label'], $row['label'], $key);
            }

            return;
        }

        if ($key === 'country') {
            $this->assertSame('countries on file', $this->field($section, 'Buttons'));
            $this->assertSame(
                Country::query()->orderBy('order')->orderBy('name')->pluck('name')->all(),
                $schema->optionValues(),
            );

            Country::query()->delete();
            $fallback = $this->registry->byKey('country')->schema($this->school->fresh());
            $documented = $this->bulletOptions($section, 'Fallback');
            $this->assertSame($fallback->optionValues(), array_column($documented, 'value'));
            foreach ($documented as $index => $row) {
                $this->assertSame($fallback->options[$index]['label'], $row['label']);
            }

            return;
        }

        if ($kind === 'active_plan') {
            $this->assertSame('active plan ids', $this->field($section, 'Buttons'), $key);
            $ids = Plan::query()->where('is_active', 1)->orderBy('order')->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->all();
            $this->assertSame($ids, $schema->optionValues(), $key);

            return;
        }

        $this->assertSame([], $schema->optionValues(), $key);
        $this->assertSame('none', strtolower($this->field($section, 'Buttons')), $key);
    }

    private function assertChoices(string $key, string $section, StepSchema $schema): void
    {
        $extra = $this->extraChoices($schema);
        if ($extra === []) {
            $this->assertSame('none', strtolower($this->field($section, 'Choices')), $key);

            return;
        }

        $parsed = $this->bulletOptions($section, 'Choices');
        $this->assertSame(array_keys($extra), array_column($parsed, 'value'), $key);
        foreach ($parsed as $row) {
            $this->assertSame($extra[$row['value']], $row['label'], $key.' '.$row['value']);
        }
    }

    /**
     * @param  array<string, mixed>  $defaults
     */
    private function assertDefaults(string $key, string $text, array $defaults): void
    {
        $this->assertNotSame('', $text, $key);

        if ($defaults === []) {
            $this->assertStringContainsString('Nothing is filled in.', $text, $key);

            return;
        }

        foreach ($defaults as $name => $value) {
            if ($key === 'plan_selection' && $name === 'plan_id') {
                $this->assertStringContainsString('Freemium', $text, $key);
                $this->assertSame($this->plan->id, $value);

                continue;
            }

            if ($key === 'academic_year' && $name === 'start') {
                $this->assertSame(now()->startOfYear()->toDateString(), $value);
                $this->assertStringContainsString('first day of this calendar year', $text);

                continue;
            }

            if ($key === 'academic_year' && $name === 'end') {
                $this->assertSame(now()->endOfYear()->toDateString(), $value);
                $this->assertStringContainsString('last day of this calendar year', $text);

                continue;
            }

            if ($value === '' || $value === null) {
                $this->assertStringContainsString('blank', strtolower($text), $key.' '.$name);

                continue;
            }

            if ($value === false) {
                $this->assertStringContainsString('not a yearly fee', $text, $key);

                continue;
            }

            if (is_array($value)) {
                $this->assertSame('terms', $key);
                foreach ($value as $term) {
                    $this->assertStringContainsString($term['name'], $text, $key);
                    $this->assertStringContainsString($this->dayMonth($term['start']), $text, $key);
                    $this->assertStringContainsString($this->dayMonth($term['end']), $text, $key);
                }

                continue;
            }

            $this->assertStringContainsString((string) $value, $text, $key.' '.$name);
        }
    }

    private function assertCatalogueRow(string $step, string $code, string $cause, string $fix): void
    {
        $cause = $this->expandTokens($cause);

        $fromMessage = StepIssueCatalog::fromMessage($step, $cause);
        if ($fromMessage->code === $code) {
            $this->assertSame($cause, $fromMessage->cause, $code);
            $this->assertSame($fix, $fromMessage->fix, $code);

            return;
        }

        $fromWhatsapp = StepIssueCatalog::whatsappVerification($cause);
        if ($fromWhatsapp->code === $code) {
            $this->assertSame($cause, $fromWhatsapp->cause, $code);
            $this->assertSame($fix, $fromWhatsapp->fix, $code);

            return;
        }

        if ($code === 'school_name.placeholder') {
            $issue = $this->registry->byKey('school_name')->saveIssues($this->school, "Ada's School")[0];
            $this->assertSame($code, $issue->code);
            $this->assertSame($cause, $issue->cause, $code);
            $this->assertSame($fix, $issue->fix, $code);

            return;
        }

        if ($code === 'plan.id_type') {
            $issue = $this->registry->byKey('plan_selection')->issues($this->school, (string) $this->plan->id)[0];
            $this->assertSame($code, $issue->code);
            $this->assertSame($cause, $issue->cause, $code);
            $this->assertSame($fix, $issue->fix, $code);

            return;
        }

        $this->fail($code.' cause mapped to '.$fromMessage->code.' / '.$fromWhatsapp->code.': '.$cause);
    }

    private function expandTokens(string $cause): string
    {
        $year = (string) date('Y');

        return strtr($cause, [
            '{year}' => $year,
            '{next}' => (string) ((int) $year + 1),
        ]);
    }

    private function dayMonth(string $iso): string
    {
        return Carbon::parse($iso)->format('j F');
    }

    /**
     * @return array<string, string>
     */
    private function stepSections(string $markdown): array
    {
        preg_match_all('/^## step: (\S+)\s*$/m', $markdown, $matches, PREG_OFFSET_CAPTURE);
        $sections = [];
        $count = count($matches[0]);

        for ($i = 0; $i < $count; $i++) {
            $key = $matches[1][$i][0];
            $start = $matches[0][$i][1] + strlen($matches[0][$i][0]);
            $end = $i + 1 < $count ? $matches[0][$i + 1][1] : strlen($markdown);
            $body = substr($markdown, $start, $end - $start);
            if ($i + 1 === $count && preg_match('/^## /m', $body, $heading, PREG_OFFSET_CAPTURE)) {
                $body = substr($body, 0, $heading[0][1]);
            }
            $sections[$key] = $body;
        }

        return $sections;
    }

    private function headingBody(string $markdown, string $heading): string
    {
        $pattern = '/^'.preg_quote($heading, '/').'\s*$/m';
        $this->assertSame(1, preg_match($pattern, $markdown, $match, PREG_OFFSET_CAPTURE), $heading);
        $rest = substr($markdown, $match[0][1] + strlen($match[0][0]));
        if (preg_match('/^## /m', $rest, $next, PREG_OFFSET_CAPTURE)) {
            $rest = substr($rest, 0, $next[0][1]);
        }

        return $rest;
    }

    /**
     * @return list<string>
     */
    private function braceTokens(string $text): array
    {
        preg_match_all('/\{([a-z_]+)\}/', $text, $matches);

        return $matches[1];
    }

    private function field(string $section, string $label): string
    {
        $pattern = '/^'.preg_quote($label, '/').':\s*(.*)$/m';
        $this->assertSame(1, preg_match($pattern, $section, $match), $label);

        return trim($match[1]);
    }

    /**
     * @return list<array{value: string, label: ?string}>
     */
    private function bulletOptions(string $section, string $label): array
    {
        $lines = preg_split('/\n/', $section);
        $capture = false;
        $rows = [];
        $labels = [
            'Question', "What you'll need", 'The school', 'The engine', 'Buttons',
            'Choices', 'Fallback', 'Default', 'Confirmation', 'Summary',
        ];

        foreach ($lines as $line) {
            if (preg_match('/^([A-Za-z][^:]{0,40}):\s*(.*)$/', $line, $match) && in_array($match[1], $labels, true)) {
                if ($capture) {
                    break;
                }
                if ($match[1] === $label) {
                    $capture = true;
                }

                continue;
            }

            if (! $capture) {
                continue;
            }

            if (preg_match('/^- `([^`]+)`(?:\s+—\s+(.*))?$/', trim($line), $match)) {
                $rows[] = [
                    'value' => $match[1],
                    'label' => isset($match[2]) ? trim($match[2]) : null,
                ];

                continue;
            }

            if (trim($line) === '') {
                if ($rows !== []) {
                    break;
                }

                continue;
            }

            if (str_starts_with($line, '#')) {
                break;
            }
        }

        return $rows;
    }

    private function issuesBlock(string $section): string
    {
        $pos = strpos($section, '### Issues');
        $this->assertNotFalse($pos);

        return substr($section, $pos);
    }

    /**
     * @return list<array{code: string, cause: string, fix: string}>
     */
    private function issueRows(string $section): array
    {
        $rows = [];
        foreach (preg_split('/\n/', $section) as $line) {
            $line = trim($line);
            if (! str_starts_with($line, '|') || str_contains($line, '---')) {
                continue;
            }
            $cells = array_map('trim', explode('|', trim($line, '|')));
            if (count($cells) < 3 || strcasecmp($cells[0], 'Code') === 0) {
                continue;
            }
            $rows[] = [
                'code' => trim($cells[0], '` '),
                'cause' => $cells[1],
                'fix' => $cells[2],
            ];
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    private function requiredLabels(StepSchema $schema): array
    {
        $labels = [];
        $walk = function (array $fields) use (&$walk, &$labels): void {
            foreach ($fields as $field) {
                if ($field->required) {
                    $labels[] = $field->label;
                }
                $walk($field->children);
            }
        };
        $walk($schema->fields);

        return $labels;
    }

    /**
     * @return list<string>
     */
    private function extensions(StepSchema $schema): array
    {
        $found = [];
        $walk = function (array $fields) use (&$walk, &$found): void {
            foreach ($fields as $field) {
                foreach ($field->extensions as $extension) {
                    $found[] = $extension;
                }
                $walk($field->children);
            }
        };
        $walk($schema->fields);

        return array_values(array_unique($found));
    }

    /**
     * Field choices that are not the step's own buttons.
     *
     * @return array<string, string>
     */
    private function extraChoices(StepSchema $schema): array
    {
        $stepValues = $schema->optionValues();
        $found = [];
        $walk = function (array $fields) use (&$walk, &$found, $stepValues): void {
            foreach ($fields as $field) {
                if (! in_array($field->name, ['country', 'plan_id'], true)) {
                    foreach ($field->options as $option) {
                        if (! in_array($option['value'], $stepValues, true)) {
                            $found[$option['value']] = $option['label'];
                        }
                    }
                }
                $walk($field->children);
            }
        };
        $walk($schema->fields);

        return $found;
    }

    private function playbook(): string
    {
        $path = base_path('docs/onboarding/playbook.md');
        $this->assertFileExists($path);
        $markdown = file_get_contents($path);
        $this->assertNotFalse($markdown);

        return $markdown;
    }
}
