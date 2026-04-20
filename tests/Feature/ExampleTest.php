<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Book courts.', false);
        $response->assertSee('Courts listed', false);
        $response->assertSee('From the court', false);
    }

    public function test_legacy_about_url_redirects_to_home_about_section(): void
    {
        $this->get('/about')->assertRedirect(route('home').'#about');
    }
}
