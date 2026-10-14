<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\ParallelTesting;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Under paratest each worker gets its own sqlite :memory: database
        // automatically (one PHP process each). The file cache store is
        // shared on disk, so give every worker its own directory.
        if ($token = ParallelTesting::token()) {
            config(['cache.stores.file.path' => storage_path('framework/cache/data/paratest-'.$token)]);
        }
    }
}
