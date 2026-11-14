<?php
/**
 * Shared bootstrap for the Kabale rehearsal scripts. Local only: the DB comes
 * from .env.rehearsal (a scratch sqlite file), never production.
 */
require_once __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

if (! str_contains((string) config('database.connections.sqlite.database'), 'rehearsal')) {
    fwrite(STDERR, "Refusing: this script only runs on the rehearsal database.\n");
    exit(1);
}

function state_path(): string { return __DIR__.'/state.json'; }
function state(): array { return is_file(state_path()) ? json_decode(file_get_contents(state_path()), true) : []; }
function save_state(array $s): void { file_put_contents(state_path(), json_encode($s, JSON_PRETTY_PRINT)); }
function say(string $m): void { echo $m."\n"; }
