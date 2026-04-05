<?php

namespace App\Livewire\Desk;

use App\Models\Booking;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::desk-portal')]
#[Title('My requests')]
class DeskMyRequests extends Component
{
    use WithPagination;

    #[Computed]
    public function courtClient()
    {
        return auth()->user()->deskCourtClient;
    }

    #[Computed]
    public function bookingsPaginator()
    {
        $c = $this->courtClient;
        if (! $c) {
            return Booking::query()->whereRaw('1 = 0')->paginate(15);
        }

        return Booking::query()
            ->where('court_client_id', $c->id)
            ->where('desk_submitted_by', auth()->id())
            ->with(['user', 'court'])
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    public function statusLabel(string $status): string
    {
        return Booking::statusDisplayLabel($status);
    }

    public function statusBadgeClasses(string $status): string
    {
        return match ($status) {
            Booking::STATUS_PENDING_APPROVAL => 'bg-amber-100 text-amber-950 dark:bg-amber-950/40 dark:text-amber-100',
            Booking::STATUS_CONFIRMED => 'bg-teal-100 text-teal-950 dark:bg-teal-950/40 dark:text-teal-100',
            Booking::STATUS_DENIED => 'bg-rose-100 text-rose-950 dark:bg-rose-950/40 dark:text-rose-100',
            Booking::STATUS_CANCELLED => 'bg-stone-200 text-stone-800 dark:bg-stone-700 dark:text-stone-200',
            Booking::STATUS_COMPLETED => 'bg-stone-200 text-stone-800 dark:bg-stone-600 dark:text-stone-100',
            default => 'bg-stone-200 text-stone-700 dark:bg-stone-700 dark:text-stone-200',
        };
    }

    public function slotSummary(Booking $b): string
    {
        $tz = config('app.timezone', 'UTC');

        return ($b->starts_at?->timezone($tz)->isoFormat('MMM D, YYYY h:mm a') ?? '—')
            .' – '
            .($b->ends_at?->timezone($tz)->isoFormat('h:mm a') ?? '—');
    }

    public function render(): View
    {
        return view('livewire.desk.desk-my-requests');
    }
}
