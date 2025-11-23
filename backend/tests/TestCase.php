<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Set up JWT authentication for tests.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure JWT guard is properly configured for testing
        config(['auth.defaults.guard' => 'api']);
    }
}
