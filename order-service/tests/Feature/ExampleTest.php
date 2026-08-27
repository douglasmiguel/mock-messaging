<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_home_page_displays_the_test_order_simulator(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Generate test order');
    }
}
