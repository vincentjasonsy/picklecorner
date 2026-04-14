<?php

namespace App\Livewire\Venue;

use App\Models\Booking;
use App\Models\CourtClient;
use App\Models\User;
use App\Models\VenueContactNote;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::venue-portal')]
#[Title('Customers')]
class VenueCrmIndex extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->administeredCourtClient !== null, 403);

        /** @var CourtClient $client */
        $client = auth()->user()->administeredCourtClient;

        $statsSub = Booking::query()
            ->where('court_client_id', $client->id)
            ->selectRaw('user_id, count(*) as venue_bookings_count, max(starts_at) as last_booking_at')
            ->groupBy('user_id');

        $notesSub = VenueContactNote::query()
            ->where('court_client_id', $client->id)
            ->selectRaw('user_id, count(*) as internal_notes_count')
            ->groupBy('user_id');

        $term = trim($this->search);

        /** @var Builder<User> $query */
        $query = User::query()
            ->joinSub($statsSub, 'venue_stats', 'users.id', '=', 'venue_stats.user_id')
            ->leftJoinSub($notesSub, 'note_stats', 'users.id', '=', 'note_stats.user_id')
            ->when($term !== '', function (Builder $q) use ($term): void {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $q->where(function (Builder $inner) use ($like): void {
                    $inner->where('users.name', 'like', $like)
                        ->orWhere('users.email', 'like', $like);
                });
            })
            ->orderByDesc('venue_stats.last_booking_at')
            ->orderBy('users.name')
            ->select('users.id', 'users.name', 'users.email')
            ->selectRaw('venue_stats.venue_bookings_count, venue_stats.last_booking_at, coalesce(note_stats.internal_notes_count, 0) as internal_notes_count');

        $contacts = $query->paginate(20)->withQueryString();

        return view('livewire.venue.venue-crm-index', [
            'courtClient' => $client,
            'contacts' => $contacts,
        ]);
    }
}
