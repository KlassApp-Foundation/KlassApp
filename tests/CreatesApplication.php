<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication()
    {
        // Force SQLite in-memory for all tests to protect the dev database
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');

        // Force the testing environment before boot. PHPUnit's <env> only
        // writes putenv/$_ENV, but Laravel's Env reads $_SERVER first
        // (ServerConstAdapter), so a shell that exports APP_ENV=local would
        // otherwise make CSRF enforce inside these tests and 419 every POST.
        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';
        $_SERVER['APP_ENV'] = 'testing';

        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // Re-apply SQLite config after boot (env may have been overridden by .env)
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);

        return $app;
    }
}
