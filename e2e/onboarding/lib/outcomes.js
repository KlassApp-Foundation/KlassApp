// Staging DB outcome checks for a journey school (read-only queries + the
// is_test flag write that marks every E2E school).
const { runStagingJson, wakeStaging } = require('./stg-bridge');

const phpStr = (v) => JSON.stringify(String(v));

function setTestFlag(email) {
    // Also a staging preflight: if no active plans exist, seed the Freemium /
    // Growth / Premium set (same values as production) so the plan step of the
    // wizard can complete. Idempotent; staging-only; never runs on production.
    return runStagingJson(`
        $plans = \\App\\Models\\Plan::where('is_active', 1)->count();
        if ($plans === 0) {
            $seed = [
                [1, 30, 'freemium', 'Freemium', 1, 0, 100, 5],
                [2, 30, 'growth', 'Growth', 2, 35, 1000, 25],
                [3, 30, 'premium', 'Premium', 3, 0, 999999, 999999],
            ];
            foreach ($seed as $r) {
                if (! \\App\\Models\\Plan::find($r[0])) {
                    $plan = new \\App\\Models\\Plan;
                    $plan->forceFill([
                        'id' => $r[0], 'cycle' => $r[1], 'name' => $r[2], 'display_name' => $r[3],
                        'order' => $r[4], 'amount' => $r[5], 'no_of_students' => $r[6],
                        'no_of_users' => $r[7], 'is_active' => 1,
                    ])->save();
                }
            }
            $plans = \\App\\Models\\Plan::where('is_active', 1)->count();
        }

        $u = \\App\\Models\\User::where('email', ${phpStr(email)})->first();
        if (! $u) { echo "<<<E2E-JSON>>>" . json_encode(['error' => 'user-not-found', 'active_plans' => $plans]); return; }
        $s = \\App\\Models\\School::find($u->school_id);
        $s->forceFill(['is_test' => 1])->save();
        echo "<<<E2E-JSON>>>" . json_encode(['school_id' => $s->id, 'school_name' => $s->name, 'active_plans' => $plans]);
    `);
}

function fetchOutcome(email) {
    wakeStaging();
    return runStagingJson(`
        $email = ${phpStr(email)};
        $u = \\App\\Models\\User::where('email', $email)->first();
        $sid = (int) ($u->school_id ?? 0);
        $school = \\App\\Models\\School::find($sid);
        $out = [
            'school_id' => $sid,
            'school' => [
                'name' => $school?->name,
                'is_test' => (int) ($school?->is_test ?? 0),
                'is_demo' => (int) ($school?->is_demo ?? 0),
                'category' => $school?->school_category,
                'curriculum' => $school?->curriculum,
                'registration_country' => $school?->registration_country,
                'emis' => $school?->ministry_code,
            ],
            'sections' => \\App\\Models\\Section::where('school_id', $sid)->orderBy('id')->pluck('name')->all(),
            'standards' => \\App\\Models\\Standard::where('school_id', $sid)->orderBy('id')->pluck('name')->all(),
            'standard_links' => \\App\\Models\\StandardLink::where('school_id', $sid)->count(),
            'streams' => \\App\\Models\\StandardLink::where('school_id', $sid)->whereNotNull('stream')->where('stream', '!=', '')->pluck('stream')->all(),
            'subjects_total' => \\App\\Models\\Subject::where('school_id', $sid)->count(),
            'subjects_by_level' => \\DB::table('subjects')
                ->join('standards', 'standards.id', '=', 'subjects.standard_id')
                ->where('subjects.school_id', $sid)->groupBy('standards.name')
                ->selectRaw('standards.name as level, count(*) as c')->pluck('c', 'level')->all(),
            'terms' => \\App\\Models\\AcademicTerm::where('school_id', $sid)->orderBy('id')->pluck('name')->all(),
            'grading_by_level' => \\DB::table('school_grading_systems')
                ->join('standards', 'standards.id', '=', 'school_grading_systems.standard_id')
                ->where('school_grading_systems.school_id', $sid)->groupBy('standards.name')
                ->selectRaw('standards.name as level, count(*) as c')->pluck('c', 'level')->all(),
            'grading_total' => \\DB::table('school_grading_systems')->where('school_id', $sid)->count(),
            'teachers' => \\App\\Models\\User::where('school_id', $sid)->where('usergroup_id', 5)->count(),
            'students' => \\App\\Models\\User::where('school_id', $sid)->where('usergroup_id', 6)->count(),
            'whatsapp' => \\App\\Models\\WhatsAppUser::where('school_id', $sid)->count(),
            'plan' => \\App\\Models\\CurrentPlan::where('school_id', $sid)->count(),
        ];
        echo "<<<E2E-JSON>>>" . json_encode($out);
    `);
}

function evaluate(out, data) {
    const checks = [];
    const findings = [];
    const add = (name, ok, detail) => checks.push({ name, ok, detail: detail ?? '' });

    // Hard checks: a pass means the school was actually set up in the DB
    // (not merely that the chat looked finished). Soft findings stay below.
    add('school flagged is_test', out.school.is_test === 1, `is_test=${out.school.is_test}`);
    add('school named E2E ...', String(out.school.name || '').startsWith('E2E '), out.school.name);
    add('category set', out.school.category === data.type.category, `category=${out.school.category}`);
    add('curriculum UNEB', String(out.school.curriculum || '').toLowerCase().includes('uneb'), `curriculum=${out.school.curriculum}`);
    add('country Uganda', String(out.school.registration_country || '').toLowerCase().includes('uganda'), `country=${out.school.registration_country}`);

    const missingSections = data.type.sections.filter((s) => !out.sections.includes(s));
    const extraSections = out.sections.filter((s) => !data.type.sections.includes(s));
    add('classes/sections for type', missingSections.length === 0, `expected=${data.type.sections.length} actual=${out.sections.length} missing=[${missingSections}] extra=[${extraSections}]`);
    add('class links exist', out.standard_links >= data.type.sections.length, `links=${out.standard_links}`);

    for (const [level, min] of Object.entries(data.type.subjectsMin)) {
        const c = out.subjects_by_level[level] ?? 0;
        add(`subjects per ${level}`, c >= min, `count=${c} (min ${min})`);
    }
    add('three terms', out.terms.length === 3, `terms=[${out.terms}]`);
    for (const level of data.type.levels) {
        const g = out.grading_by_level[level] ?? 0;
        add(`grading for ${level}`, g > 0, `rows=${g}`);
    }
    add('whatsapp linked', out.whatsapp >= 1, `rows=${out.whatsapp}`);
    add('plan selected', out.plan >= 1, `rows=${out.plan}`);
    add('school id present', Number(out.school_id) > 0, `school_id=${out.school_id}`);

    if (data.mode === 'manual') {
        // Streams are section-per-stream: '<Class> <Stream>' (e.g. 'Primary One Blue').
        const streamSection = `${data.type.streamClassExample} ${data.type.streamName}`;
        add('stream added persists', out.sections.includes(streamSection), `looking for section "${streamSection}"`);
    } else {
        findings.push(`Toshi run streams: [${out.streams}] (not scripted in chat; manual runs cover streams)`);
    }

    if (data.type.comboExamples) {
        findings.push(`A-level combinations ${data.type.comboExamples}: no dedicated combination entity found for them in outcomes; streams are the closest mechanism (flagged for review)`);
        const hasGP = (out.subjects_total > 0);
        if (hasGP) findings.push('A-level subjects seeded include General Paper etc.; principal/subsidiary classification not modelled (flagged)');
    }

    const failed = checks.filter((c) => !c.ok);
    return { checks, findings, failed };
}

module.exports = { setTestFlag, fetchOutcome, evaluate };
