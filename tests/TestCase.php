<?php

namespace Tests;

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Send the current asset version, so Inertia requests in tests are not
        // turned away with "409 Conflict" when the frontend has been built.
        $this->withHeader('X-Inertia-Version', (string) app(HandleInertiaRequests::class)->version(request()));
    }
}
