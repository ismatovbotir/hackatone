<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // No test may reach Telegram, Hugging Face or anything else: unfaked HTTP calls fail the test.
        Http::preventStrayRequests();
    }
}
