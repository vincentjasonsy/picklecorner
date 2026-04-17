<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    public function test_terms_and_conditions_page_renders(): void
    {
        $this->get(route('terms'))
            ->assertOk()
            ->assertSee('Terms &amp; conditions', false);
    }

    public function test_privacy_policy_page_renders(): void
    {
        $this->get(route('privacy-policy'))
            ->assertOk()
            ->assertSee('Privacy policy', false);
    }

    public function test_refund_policy_page_renders(): void
    {
        $this->get(route('refund-policy'))
            ->assertOk()
            ->assertSee('Refund policy', false);
    }

    public function test_booking_cancellation_policy_page_renders(): void
    {
        $this->get(route('booking-cancellation-policy'))
            ->assertOk()
            ->assertSee('Booking &amp; cancellation policy', false);
    }
}
