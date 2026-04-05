<?php

use App\Models\Booking;
use App\Models\CourtClient;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin'), Title('Overview')] class extends Component
{
    #[Computed]
    public function stats(): array
    {
        return [
            'users' => User::query()->count(),
            'clients' => CourtClient::query()->count(),
            'bookings_total' => Booking::query()->count(),
            'bookings_month' => Booking::query()
                ->where('starts_at', '>=', now()->startOfMonth())
                ->where('status', '!=', Booking::STATUS_CANCELLED)
                ->count(),
            'revenue_month_cents' => (int) Booking::query()
                ->where('starts_at', '>=', now()->startOfMonth())
                ->where('status', '!=', Booking::STATUS_CANCELLED)
                ->sum('amount_cents'),
        ];
    }
};
?>

<div class="space-y-8">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div
            class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900"
        >
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                Users
            </p>
            <p class="mt-2 font-display text-3xl font-bold text-zinc-900 dark:text-white">
                {{ number_format($this->stats['users']) }}
            </p>
        </div>
        <div
            class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900"
        >
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                Court clients
            </p>
            <p class="mt-2 font-display text-3xl font-bold text-zinc-900 dark:text-white">
                {{ number_format($this->stats['clients']) }}
            </p>
        </div>
        <div
            class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900"
        >
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                Bookings (this month)
            </p>
            <p class="mt-2 font-display text-3xl font-bold text-zinc-900 dark:text-white">
                {{ number_format($this->stats['bookings_month']) }}
            </p>
            <p class="mt-1 text-xs text-zinc-500">Excludes cancelled</p>
        </div>
        <div
            class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900"
        >
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                Revenue (this month)
            </p>
            <p class="mt-2 font-display text-3xl font-bold text-zinc-900 dark:text-white">
                @if ($this->stats['revenue_month_cents'] > 0)
                    {{ \App\Support\Money::formatMinor($this->stats['revenue_month_cents']) }}
                @else
                    —
                @endif
            </p>
            <p class="mt-1 text-xs text-zinc-500">Sum of booking amounts</p>
        </div>
    </div>

    <p class="text-sm text-zinc-600 dark:text-zinc-400">
        Use the sidebar for user management, venue pricing, and detailed reports.
    </p>
</div>
