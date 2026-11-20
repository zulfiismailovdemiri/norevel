<?php

namespace Tests\Feature;

use Tests\TestCase;

// noredock sets DB_CONNECTION=pgsql for the running app. phpunit.xml forces SQLite in memory
// (force="true"), so tests can never touch the development database. This test guards that.
class TestDatabaseTest extends TestCase
{
    public function test_tests_use_sqlite_in_memory(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
    }
}
