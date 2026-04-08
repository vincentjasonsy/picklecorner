<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToolsLandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_tools_route_redirects_to_home_with_fragment(): void
    {
        $response = $this->get('/tools');

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertIsString($location);
        $this->assertStringContainsString('#tools', $location);
        $this->assertStringStartsWith(rtrim(route('home'), '/'), $location);
    }
}
