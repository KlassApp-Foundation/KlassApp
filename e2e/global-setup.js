const { execFileSync } = require('node:child_process');

module.exports = async () => {
    const baseURL =
        process.env.PLAYWRIGHT_BASE_URL
        || process.env.APP_URL
        || 'http://127.0.0.1:8000';

    const isLocalBaseUrl = /^https?:\/\/(127\.0\.0\.1|localhost)(:\d+)?/i.test(baseURL);

    if (!isLocalBaseUrl) {
        return;
    }

    const tinker = `
        $now = now();
        foreach ([[1, 'superadmin'], [3, 'schooladmin'], [5, 'teacher'], [6, 'student']] as [$id, $name]) {
            \\Illuminate\\Support\\Facades\\DB::table('usergroups')->updateOrInsert(
                ['id' => $id],
                ['name' => $name, 'created_at' => $now, 'updated_at' => $now]
            );
        }
        \\Illuminate\\Support\\Facades\\DB::table('plans')->updateOrInsert(
            ['id' => 1],
            [
                'cycle' => 30,
                'name' => 'Freemium',
                'display_name' => 'Freemium',
                'order' => 1,
                'is_active' => 1,
                'amount' => 0,
                'no_of_students' => 0,
                'no_of_users' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        echo "playwright-local-seed-ok\\n";
    `.replace(/\n\s+/g, ' ').trim();

    execFileSync('php', ['artisan', 'tinker', '--execute', tinker], {
        cwd: process.cwd(),
        stdio: 'inherit',
    });
};
