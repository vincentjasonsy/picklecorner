<?php

namespace Tests\Feature;

use Tests\TestCase;

class HttpErrorPagesTest extends TestCase
{
    public function test_unknown_route_renders_custom_404_page(): void
    {
        $response = $this->get('/__missing_route_'.bin2hex(random_bytes(8)));

        $response->assertNotFound();
        $response->assertSee('Page not found', false);
        $response->assertSee('Back to home', false);
    }
}
