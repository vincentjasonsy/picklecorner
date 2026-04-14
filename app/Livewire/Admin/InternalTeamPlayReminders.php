<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Models\UserType;
use App\Notifications\InternalTeamPlayReminderNotification;
use App\Support\InternalTeamPlayReminder;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin')]
#[Title('Team play reminders')]
class InternalTeamPlayReminders extends Component
{
    use WithPagination;

    private int $perPage = 25;

    /** all | dormant_10 | eligible | unsubscribed | upcoming | never_booked */
    #[Url]
    public string $filter = 'all';

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function runScheduledReminders(): void
    {
        Artisan::call('internal:send-team-play-reminders');
        $output = trim(Artisan::output());
        session()->flash('status', $output !== '' ? $output : 'Scheduled reminder job finished.');
    }

    public function sendReminderNow(string $userId): void
    {
        $user = User::query()->findOrFail($userId);
        if (! $user->isPlayer()) {
            abort(403);
        }

        if ($user->internal_team_play_reminders_unsubscribed_at !== null) {
            session()->flash('warning', "{$user->email} has unsubscribed from booking reminders.");

            return;
        }

        $daysPast = InternalTeamPlayReminder::daysSinceLastPastBookingAsBooker($user);
        $days = $daysPast !== null
            ? max($daysPast, InternalTeamPlayReminder::DAYS_THRESHOLD)
            : InternalTeamPlayReminder::DAYS_THRESHOLD;

        $courts = InternalTeamPlayReminder::courtSuggestionsForUser($user);
        $user->notify(new InternalTeamPlayReminderNotification($days, $courts));

        session()->flash('status', "Reminder sent to {$user->email} (email + in-app notification).");
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function allRows(): Collection
    {
        $typeId = UserType::query()->where('slug', UserType::SLUG_USER)->value('id');
        if ($typeId === null) {
            return collect();
        }

        return User::query()
            ->where('user_type_id', $typeId)
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => InternalTeamPlayReminder::dashboardRow($u));
    }

    public function render(): View
    {
        $allRows = $this->allRows();
        $rows = $allRows->filter(function (array $row): bool {
            return match ($this->filter) {
                'dormant_10' => $row['dormant_10_plus'] === true,
                'eligible' => $row['eligible_for_scheduled_reminder'] === true,
                'unsubscribed' => $row['unsubscribed'] === true,
                'upcoming' => $row['latest_is_upcoming'] === true,
                'never_booked' => $row['days_since_last_past'] === null,
                default => true,
            };
        })->values();

        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        $rowsPaginator = new LengthAwarePaginator(
            $rows->forPage($currentPage, $this->perPage)->values(),
            $rows->count(),
            $this->perPage,
            $currentPage,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => request()->query(),
            ],
        );

        $stats = [
            'members' => $allRows->count(),
            'dormant_10' => $allRows->where('dormant_10_plus', true)->count(),
            'eligible' => $allRows->where('eligible_for_scheduled_reminder', true)->count(),
            'unsubscribed' => $allRows->where('unsubscribed', true)->count(),
            'never_booked' => $allRows->filter(fn (array $r) => $r['days_since_last_past'] === null)->count(),
        ];

        return view('livewire.admin.internal-team-play-reminders', [
            'rows' => $rowsPaginator,
            'stats' => $stats,
        ]);
    }
}
