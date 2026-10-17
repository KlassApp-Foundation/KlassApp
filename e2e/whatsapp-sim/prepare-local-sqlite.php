<?php

/**
 * LOCAL SQLite only: relax the enum CHECK constraints on message_delivery_log.
 *
 * Why: on MySQL (production and staging run with 'strict' => false in
 * config/database.php) an enum column silently accepts values outside its list,
 * and the WhatsApp code logs categories such as "interactive", "document" or
 * "menu_greeting" and the inbound status "received". SQLite enforces the enum as
 * a CHECK constraint, so on a local SQLite database every such insert fails and
 * the webhook answers 500. This script rebuilds that one table without the CHECK
 * on "category" and "status" so a local run behaves like MySQL. It never touches
 * MySQL, refuses unless APP_ENV=local and DB_CONNECTION=sqlite, and is safe to
 * run twice.
 *
 *   php e2e/whatsapp-sim/prepare-local-sqlite.php [path/to/app]
 */

$appDir = rtrim($argv[1] ?? getcwd(), '/');
$envFile = $appDir.'/.env';
if (! is_file($envFile)) {
    fwrite(STDERR, "No .env in {$appDir}\n");
    exit(1);
}

$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES) as $line) {
    if (preg_match('/^\s*([A-Z0-9_]+)\s*=\s*(.*)\s*$/', $line, $m)) {
        $env[$m[1]] = trim($m[2], "\"'");
    }
}

if (($env['APP_ENV'] ?? '') !== 'local' || ($env['DB_CONNECTION'] ?? '') !== 'sqlite') {
    fwrite(STDERR, "Refusing: needs APP_ENV=local and DB_CONNECTION=sqlite.\n");
    exit(1);
}

$db = $env['DB_DATABASE'] ?? '';
if ($db === '' || $db === ':memory:' || ! is_file($db)) {
    fwrite(STDERR, "DB_DATABASE must be an existing SQLite file.\n");
    exit(1);
}

$pdo = new PDO('sqlite:'.$db, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$sql = $pdo->query("select sql from sqlite_master where type = 'table' and name = 'message_delivery_log'")->fetchColumn();
if (! $sql) {
    fwrite(STDERR, "Table message_delivery_log not found (run php artisan migrate first).\n");
    exit(1);
}

$relaxed = preg_replace('/\s+check\s*\(\s*"(category|status)"\s+in\s*\([^)]*\)\s*\)/i', '', $sql);
if ($relaxed === $sql) {
    echo "message_delivery_log: already relaxed, nothing to do.\n";
    exit(0);
}

$indexes = $pdo->query("select sql from sqlite_master where type = 'index' and tbl_name = 'message_delivery_log' and sql is not null")->fetchAll(PDO::FETCH_COLUMN);

$pdo->beginTransaction();
$pdo->exec('alter table "message_delivery_log" rename to "message_delivery_log_sim_old"');
$pdo->exec($relaxed);
$pdo->exec('insert into "message_delivery_log" select * from "message_delivery_log_sim_old"');
$pdo->exec('drop table "message_delivery_log_sim_old"');
foreach ($indexes as $index) {
    $pdo->exec($index);
}
$pdo->commit();

echo "message_delivery_log: CHECK constraints on category and status removed (local SQLite only).\n";
