<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_home_page_shows_the_schoolruns_landing_page(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Adino School Systems');
        $response->assertSee(route('login'), false);
    }
}
