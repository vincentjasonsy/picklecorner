<?php

namespace App\Livewire\Admin;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\CourtClient;
use App\Models\OpenPlayParticipant;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class UserSummary extends Component
{
    use WithPagination;

    public User $user;

    public ?string $scopedCourtClientId = null;

    public function mount(User $user): void
    {
        $this->user = $user->load([
            'userType',
            'deskCourtClient:id,name',
            'administeredCourtClient:id,name',
        ]);

        if (request()->routeIs('admin.users.summary')) {
            abort_unless(auth()->user()?->isSuperAdmin(), 403);
            $this->scopedCourtClientId = null;

            return;
        }

        if (request()->routeIs('venue.customers.summary')) {
            $auth = auth()->user();
            abort_unless($auth?->isCourtAdmin(), 403);
            $client = $auth->administeredCourtClient;
            abort_unless($client !== null, 403);
            $hasBookingHere = Booking::query()
                ->where('court_client_id', $client->id)
                ->where('user_id', $user->id)
                ->exists();
            abort_unless($hasBookingHere, 404);
            $this->scopedCourtClientId = $client->id;

            return;
        }

        abort(404);
    }

    public function statusBadgeClasses(string $status): string
    {
        return match ($status) {
            Booking::STATUS_CONFIRMED, Booking::STATUS_COMPLETED => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200',
            Booking::STATUS_PENDING_APPROVAL => 'bg-amber-100 text-amber-900 dark:bg-amber-950/50 dark:text-amber-200',
            Booking::STATUS_CANCELLED => 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200',
            Booking::STATUS_DENIED => 'bg-red-100 text-red-800 dark:bg-red-950/50 dark:text-red-200',
            default => 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200',
        };
    }

    public function render(): View
    {
        $venueScoped = $this->scopedCourtClientId !== null;
        $uid = $this->user->id;
        $ccid = $this->scopedCourtClientId;

        $bookingsQuery = $this->user->bookings()->with(['courtClient:id,name,city', 'court:id,name']);
        if ($ccid !== null) {
            $bookingsQuery->where('court_client_id', $ccid);
        }
        $bookingsPaginator = $bookingsQuery->orderByDesc('starts_at')->paginate(12);

        $coachedBookings = collect();
        if ($this->user->isCoach()) {
            $q = Booking::query()
                ->where('coach_user_id', $uid)
                ->with(['courtClient:id,name,city', 'court:id,name', 'user:id,name,email']);
            if ($ccid !== null) {
                $q->where('court_client_id', $ccid);
            }
            $coachedBookings = $q->orderByDesc('starts_at')->limit(20)->get();
        }

        $openPlayQ = OpenPlayParticipant::query()
            ->where('user_id', $uid)
            ->with(['booking' => fn ($q) => $q->with(['courtClient:id,name', 'court:id,name'])]);
        if ($ccid !== null) {
            $openPlayQ->whereHas('booking', fn ($q) => $q->where('court_client_id', $ccid));
        }
        $openPlayParticipants = $openPlayQ->orderByDesc('created_at')->limit(20)->get();

        $activityQ = ActivityLog::query()->where('user_id', $uid);
        if ($ccid !== null) {
            $activityQ->where('court_client_id', $ccid);
        }
        $activityLogs = $activityQ->orderByDesc('created_at')->limit(30)->get();

        $now = now();
        $bookingsBase = Booking::query()->where('user_id', $uid);
        $coachBase = Booking::query()->where('coach_user_id', $uid);
        $openPlayBase = OpenPlayParticipant::query()->where('user_id', $uid);
        if ($ccid !== null) {
            $bookingsBase->where('court_client_id', $ccid);
            $coachBase->where('court_client_id', $ccid);
            $openPlayBase->whereHas('booking', fn ($q) => $q->where('court_client_id', $ccid));
        }

        $stats = [
            'bookings_total' => (clone $bookingsBase)->count(),
            'bookings_upcoming' => (clone $bookingsBase)
                ->where('starts_at', '>=', $now)
                ->whereNotIn('status', [Booking::STATUS_CANCELLED, Booking::STATUS_DENIED])
                ->count(),
            'coached_total' => $this->user->isCoach() ? (clone $coachBase)->count() : 0,
            'open_play_total' => (clone $openPlayBase)->count(),
        ];

        $venueCourtClient = null;
        $venueBackUrl = null;
        if ($venueScoped && $ccid !== null) {
            $venueCourtClient = CourtClient::query()->find($ccid);
            $venueBackUrl = route('venue.crm.index');
        }

        $view = view('livewire.admin.user-summary', [
            'bookingsPaginator' => $bookingsPaginator,
            'coachedBookings' => $coachedBookings,
            'openPlayParticipants' => $openPlayParticipants,
            'activityLogs' => $activityLogs,
            'stats' => $stats,
            'venueScoped' => $venueScoped,
            'venueCourtClient' => $venueCourtClient,
            'venueBackUrl' => $venueBackUrl,
        ]);

        if ($venueScoped) {
            return $view->layout('layouts::venue-portal')->title('Customer summary');
        }

        return $view->layout('layouts::admin')->title('User summary');
    }
}
