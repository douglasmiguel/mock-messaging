<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_home_redirects_to_metrics(): void
    {
        $this->get('/')->assertRedirect('/metrics');
    }
}
