/**
 * Generates run-unique TEST-E2E- prefixed school/admin/teacher/student data
 * with identical shape for both manual-selfserve and Toshi journeys.
 */
function generateRunData() {
    const stamp = Date.now();
    const suffix = String(stamp).slice(-6);

    return {
        stamp,
        suffix,
        schoolName: `TEST-E2E-${suffix} Academy`,
        admin: {
            name: `E2E Admin ${suffix}`,
            email: `e2e.admin.${suffix}@klassapp.test`,
            phone: `070${String(stamp).slice(-7)}`,
        },
        password: 'Password123!',
        curriculum: 'UNEB',
        country: 'Uganda',
        emisCode: `EMIS${suffix}`,
        teachers: [
            { name: `E2E Teacher A ${suffix}`, email: `e2e.teacher.a.${suffix}@klassapp.test` },
            { name: `E2E Teacher B ${suffix}`, email: `e2e.teacher.b.${suffix}@klassapp.test` },
        ],
        students: [
            { name: `E2E Student A ${suffix}`, email: `e2e.student.a.${suffix}@klassapp.test` },
            { name: `E2E Student B ${suffix}`, email: `e2e.student.b.${suffix}@klassapp.test` },
            { name: `E2E Student C ${suffix}`, email: `e2e.student.c.${suffix}@klassapp.test` },
        ],
        classes: ['Primary 1', 'Primary 2'],
        subjects: [
            { name: 'English Language', code: 'ENG' },
            { name: 'Mathematics', code: 'MATH' },
            { name: 'Literacy I', code: 'LIT1' },
        ],
        terms: [
            { name: 'Term I', start: '02-01', end: '04-30' },
            { name: 'Term II', start: '05-01', end: '08-31' },
            { name: 'Term III', start: '09-01', end: '12-31' },
        ],
        fees: [
            { name: 'Tuition', amount: 500000 },
            { name: 'Development', amount: 200000 },
        ],
        plan: { name: 'Freemium' },
    };
}

module.exports = { generateRunData };
