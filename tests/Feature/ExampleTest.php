<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('images/slider/slide-1.jpg', false);
        $response->assertSee('Featured visuals', false);
        $response->assertSee('From the court', false);
    }

    public function test_legacy_about_url_redirects_to_home_about_section(): void
    {
        $this->get('/about')->assertRedirect(route('home').'#about');
    }
}
