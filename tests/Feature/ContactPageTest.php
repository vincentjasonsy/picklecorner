<?php

namespace Tests\Feature;

use App\Livewire\ContactPage;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_contact_page(): void
    {
        $this->seed(UserTypeSeeder::class);

        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('Reach out to us!', false)
            ->assertSee(config('mail.from.address') ?: 'support@picklecorner.ph', false);
    }

    public function test_contact_page_livewire_renders(): void
    {
        $this->seed(UserTypeSeeder::class);

        Livewire::test(ContactPage::class)
            ->assertOk()
            ->assertSee('Reach out to us!');
    }
}
