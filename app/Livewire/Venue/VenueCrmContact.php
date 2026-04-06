<?php

namespace App\Livewire\Venue;

use App\Models\Booking;
use App\Models\CourtClient;
use App\Models\User;
use App\Models\VenueContactNote;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::venue-portal')]
class VenueCrmContact extends Component
{
    public User $contact;

    public string $newNoteBody = '';

    public function mount(): void
    {
        $client = auth()->user()->administeredCourtClient;
        abort_unless($client !== null, 403);

        $isContact = Booking::query()
            ->where('court_client_id', $client->id)
            ->where('user_id', $this->contact->id)
            ->exists();

        abort_unless($isContact, 404);
    }

    public function addNote(): void
    {
        $this->validate([
            'newNoteBody' => ['required', 'string', 'max:10000'],
        ]);

        /** @var CourtClient $client */
        $client = auth()->user()->administeredCourtClient;
        abort_unless($client !== null, 403);

        $note = VenueContactNote::query()->create([
            'court_client_id' => $client->id,
            'user_id' => $this->contact->id,
            'body' => trim($this->newNoteBody),
            'created_by_user_id' => auth()->id(),
        ]);

        ActivityLogger::log(
            'venue.crm.note_created',
            ['contact_user_id' => $this->contact->id],
            $note,
            'Internal customer note added',
        );

        $this->newNoteBody = '';
    }

    public function render(): View
    {
        /** @var CourtClient $client */
        $client = auth()->user()->administeredCourtClient;
        abort_unless($client !== null, 403);

        $tz = config('app.timezone', 'UTC');

        $bookingStats = Booking::query()
            ->where('court_client_id', $client->id)
            ->where('user_id', $this->contact->id)
            ->selectRaw('count(*) as c, min(starts_at) as first_at, max(starts_at) as last_at, sum(coalesce(amount_cents, 0)) as revenue_cents')
            ->first();

        /** @var Collection<int, Booking> $recentBookings */
        $recentBookings = Booking::query()
            ->with(['court:id,name'])
            ->where('court_client_id', $client->id)
            ->where('user_id', $this->contact->id)
            ->orderByDesc('starts_at')
            ->limit(8)
            ->get();

        $notes = VenueContactNote::query()
            ->where('court_client_id', $client->id)
            ->where('user_id', $this->contact->id)
            ->with('createdBy:id,name')
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.venue.venue-crm-contact', [
            'courtClient' => $client,
            'bookingStats' => $bookingStats,
            'recentBookings' => $recentBookings,
            'notes' => $notes,
            'tz' => $tz,
        ])->title($this->contact->name.' — Customers');
    }
}
