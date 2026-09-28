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

        $detectedEnv = app()->environment();
        if ($detectedEnv !== 'testing') {
            fwrite(STDERR, sprintf(
                "[test-harness] abort: app environment is '%s', expected 'testing'. A shell or service is overriding phpunit's environment; refusing to run tests in the wrong mode.\n",
                $detectedEnv
            ));
            exit(1);
        }

        $defaultConnection = (string) config('database.default');
        $defaultDatabase = (string) config("database.connections.{$defaultConnection}.database");
        if ($defaultConnection !== 'sqlite' || $defaultDatabase !== ':memory:') {
            fwrite(STDERR, sprintf(
                "[test-harness] abort: default database is '%s' (%s), expected sqlite :memory: per phpunit.xml. Refusing to run migrations or tests against a non-test database.\n",
                $defaultConnection,
                $defaultDatabase
            ));
            exit(1);
        }

        // Re-apply SQLite config after boot (env may have been overridden by .env)
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);

        return $app;
    }
}
