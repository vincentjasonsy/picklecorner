<?php

namespace Tests\Feature;

use App\Livewire\Venue\CourtGalleryEditor;
use App\Livewire\Venue\VenueClientGallery;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\CourtClientGalleryImage;
use App\Models\CourtGalleryImage;
use App\Models\User;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class VenueGalleryUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_court_admin_can_upload_venue_gallery_image(): void
    {
        Storage::fake('public');
        $this->seed(UserTypeSeeder::class);

        $admin = User::factory()->courtAdmin()->create();
        $client = CourtClient::factory()->forAdmin($admin)->create();

        $file = UploadedFile::fake()->image('venue.jpg', 800, 600);

        Livewire::actingAs($admin)
            ->test(VenueClientGallery::class, ['courtClientId' => $client->id])
            ->set('uploads', [$file])
            ->call('saveUploads')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('court_client_gallery_images', 1);
        $row = CourtClientGalleryImage::query()->first();
        $this->assertSame($client->id, $row->court_client_id);
        $this->assertNull($row->approved_at);
        Storage::disk('public')->assertExists($row->path);
    }

    public function test_court_admin_can_upload_court_gallery_image(): void
    {
        Storage::fake('public');
        $this->seed(UserTypeSeeder::class);

        $admin = User::factory()->courtAdmin()->create();
        $client = CourtClient::factory()->forAdmin($admin)->create();
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Outdoor 1',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $file = UploadedFile::fake()->image('court.jpg', 600, 400);

        Livewire::actingAs($admin)
            ->test(CourtGalleryEditor::class, ['courtId' => $court->id])
            ->set('uploads', [$file])
            ->call('saveUploads')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('court_gallery_images', 1);
        $row = CourtGalleryImage::query()->first();
        $this->assertSame($court->id, $row->court_id);
        $this->assertNull($row->approved_at);
        Storage::disk('public')->assertExists($row->path);
    }

    public function test_venue_booking_page_shows_carousel_when_gallery_has_images(): void
    {
        $this->seed(UserTypeSeeder::class);

        $admin = User::factory()->courtAdmin()->create();
        $client = CourtClient::factory()->forAdmin($admin)->create();

        $now = now();
        CourtClientGalleryImage::query()->create([
            'court_client_id' => $client->id,
            'path' => 'court-clients/'.$client->id.'/gallery/test.jpg',
            'sort_order' => 0,
            'alt_text' => 'A',
            'approved_at' => $now,
        ]);
        CourtClientGalleryImage::query()->create([
            'court_client_id' => $client->id,
            'path' => 'court-clients/'.$client->id.'/gallery/test2.jpg',
            'sort_order' => 1,
            'alt_text' => 'B',
            'approved_at' => $now,
        ]);

        $this->get(route('book-now.venue.book', $client))
            ->assertOk()
            ->assertSee('aria-roledescription="carousel"', false);
    }

    public function test_pending_venue_gallery_images_do_not_show_public_carousel(): void
    {
        $this->seed(UserTypeSeeder::class);

        $admin = User::factory()->courtAdmin()->create();
        $client = CourtClient::factory()->forAdmin($admin)->create();

        CourtClientGalleryImage::query()->create([
            'court_client_id' => $client->id,
            'path' => 'court-clients/'.$client->id.'/gallery/pending.jpg',
            'sort_order' => 0,
            'alt_text' => 'X',
            'approved_at' => null,
        ]);
        CourtClientGalleryImage::query()->create([
            'court_client_id' => $client->id,
            'path' => 'court-clients/'.$client->id.'/gallery/pending2.jpg',
            'sort_order' => 1,
            'alt_text' => 'Y',
            'approved_at' => null,
        ]);

        $this->get(route('book-now.venue.book', $client))
            ->assertOk()
            ->assertDontSee('aria-roledescription="carousel"', false);
    }

    public function test_super_admin_upload_auto_approves_venue_gallery(): void
    {
        Storage::fake('public');
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();
        $client = CourtClient::factory()->create();

        $file = UploadedFile::fake()->image('v.jpg', 400, 300);

        Livewire::actingAs($super)
            ->test(VenueClientGallery::class, ['courtClientId' => $client->id])
            ->set('uploads', [$file])
            ->call('saveUploads')
            ->assertHasNoErrors();

        $row = CourtClientGalleryImage::query()->first();
        $this->assertNotNull($row->approved_at);
    }

    public function test_super_admin_can_open_gallery_approvals_page(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)
            ->get(route('admin.gallery-approvals'))
            ->assertOk()
            ->assertSee('Gallery image approvals', false);
    }

    public function test_venue_gallery_rejects_upload_when_six_images_exist(): void
    {
        Storage::fake('public');
        $this->seed(UserTypeSeeder::class);

        $admin = User::factory()->courtAdmin()->create();
        $client = CourtClient::factory()->forAdmin($admin)->create();

        for ($i = 0; $i < VenueClientGallery::MAX_IMAGES; $i++) {
            CourtClientGalleryImage::query()->create([
                'court_client_id' => $client->id,
                'path' => 'court-clients/'.$client->id.'/gallery/existing-'.$i.'.jpg',
                'sort_order' => $i,
                'alt_text' => null,
                'approved_at' => now(),
            ]);
        }

        $file = UploadedFile::fake()->image('extra.jpg', 100, 100);

        Livewire::actingAs($admin)
            ->test(VenueClientGallery::class, ['courtClientId' => $client->id])
            ->set('uploads', [$file])
            ->call('saveUploads')
            ->assertHasErrors(['uploads']);

        $this->assertDatabaseCount('court_client_gallery_images', VenueClientGallery::MAX_IMAGES);
    }

    public function test_venue_gallery_rejects_batch_larger_than_remaining_slots(): void
    {
        Storage::fake('public');
        $this->seed(UserTypeSeeder::class);

        $admin = User::factory()->courtAdmin()->create();
        $client = CourtClient::factory()->forAdmin($admin)->create();

        for ($i = 0; $i < 4; $i++) {
            CourtClientGalleryImage::query()->create([
                'court_client_id' => $client->id,
                'path' => 'court-clients/'.$client->id.'/gallery/existing-'.$i.'.jpg',
                'sort_order' => $i,
                'alt_text' => null,
                'approved_at' => now(),
            ]);
        }

        $files = [
            UploadedFile::fake()->image('a.jpg', 100, 100),
            UploadedFile::fake()->image('b.jpg', 100, 100),
            UploadedFile::fake()->image('c.jpg', 100, 100),
        ];

        Livewire::actingAs($admin)
            ->test(VenueClientGallery::class, ['courtClientId' => $client->id])
            ->set('uploads', $files)
            ->call('saveUploads')
            ->assertHasErrors(['uploads']);

        $this->assertDatabaseCount('court_client_gallery_images', 4);
    }

    public function test_court_gallery_rejects_upload_when_six_images_exist(): void
    {
        Storage::fake('public');
        $this->seed(UserTypeSeeder::class);

        $admin = User::factory()->courtAdmin()->create();
        $client = CourtClient::factory()->forAdmin($admin)->create();
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        for ($i = 0; $i < CourtGalleryEditor::MAX_IMAGES; $i++) {
            CourtGalleryImage::query()->create([
                'court_id' => $court->id,
                'path' => 'courts/'.$court->id.'/gallery/existing-'.$i.'.jpg',
                'sort_order' => $i,
                'alt_text' => null,
                'approved_at' => now(),
            ]);
        }

        $file = UploadedFile::fake()->image('extra.jpg', 100, 100);

        Livewire::actingAs($admin)
            ->test(CourtGalleryEditor::class, ['courtId' => $court->id])
            ->set('uploads', [$file])
            ->call('saveUploads')
            ->assertHasErrors(['uploads']);

        $this->assertDatabaseCount('court_gallery_images', CourtGalleryEditor::MAX_IMAGES);
    }
}
