// Journey definitions for the six onboarding journeys (3 school types x 2 modes).

const TYPES = {
    primary: {
        id: 'primary',
        label: 'Nursery & Primary',
        category: 'primary_nursery',
        categoryAnswer: 'Primary + Nursery',
        levels: ['nursery', 'primary'],
        sections: [
            'Baby Class', 'Middle Class', 'Top Class',
            'Primary One', 'Primary Two', 'Primary Three', 'Primary Four',
            'Primary Five', 'Primary Six', 'Primary Seven',
        ],
        streamClassExample: 'Primary One',
        streamName: 'Blue',
        studentClass: 'Primary One',
        subjectsMin: { nursery: 4, primary: 4 },
    },
    olevel: {
        id: 'olevel',
        label: 'Secondary O-Level',
        category: 'o_level',
        categoryAnswer: 'O-Level',
        levels: ['o-level'],
        sections: ['Senior One', 'Senior Two', 'Senior Three', 'Senior Four'],
        streamClassExample: 'Senior One',
        streamName: 'Blue',
        studentClass: 'Senior One',
        subjectsMin: { 'o-level': 7 },
    },
    oalevel: {
        id: 'oalevel',
        label: 'Secondary O & A-Level',
        category: 'o_a_level',
        categoryAnswer: 'O-Level + A-Level',
        levels: ['o-level', 'a-level'],
        sections: ['Senior One', 'Senior Two', 'Senior Three', 'Senior Four', 'Senior Five', 'Senior Six'],
        streamClassExample: 'Senior Five',
        streamName: 'PCM',
        studentClass: 'Senior One',
        subjectsMin: { 'o-level': 7, 'a-level': 11 },
        comboExamples: ['PCM', 'BCM', 'HEG'],
    },
};

function today() {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function buildJourneyData({ typeId, mode }) {
    const type = TYPES[typeId];
    if (!type) throw new Error(`unknown school type: ${typeId}`);
    const stamp = Date.now();
    const suffix = String(stamp).slice(-6);
    // Person-name fields accept letters/spaces/'/- only — map digits to letters.
    const nameTag = suffix.split('').map((d) => String.fromCharCode(97 + Number(d))).join('');
    // Non-real Ugandan-shaped numbers only (070-prefix, 000-padded block).
    const phoneLocal = `070${suffix}${String(stamp % 10)}`;
    const phoneE164 = `+256${phoneLocal.slice(1)}`;
    return {
        stamp,
        suffix,
        nameTag,
        date: today(),
        type,
        mode,
        schoolName: `E2E ${type.label} ${mode === 'manual' ? 'Manual' : 'Toshi'} ${today()} ${suffix}`,
        admin: {
            name: `Suite Admin ${nameTag}`,
            email: `e2e.${typeId}.${mode}.${suffix}@example.com`,
            phoneLocal,
            phoneE164,
        },
        password: 'Password123!',
        emisCode: `EMIS-E2E-${suffix}`,
        teachers: [`Suite Teacher One ${nameTag}`, `Suite Teacher Two ${nameTag}`],
        students: [`Suite Pupil One ${nameTag}`, `Suite Pupil Two ${nameTag}`, `Suite Pupil Three ${nameTag}`],
        fee: { name: 'Tuition', amount: '500000' },
    };
}

module.exports = { TYPES, buildJourneyData };
