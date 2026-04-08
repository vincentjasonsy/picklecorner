@php
    $tz = config('app.timezone', 'UTC');
@endphp

<div class="space-y-8">
    <div class="space-y-2">
        <p class="font-display text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
            Member engagement
        </p>
        <h2 class="font-display text-2xl font-bold text-zinc-900 dark:text-white">
            Team play reminders
        </h2>
        <p class="max-w-3xl text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
            Standard member (player) accounts get nudged after
            <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ \App\Support\InternalTeamPlayReminder::DAYS_THRESHOLD }}+</span>
            days without a past booking as the booker (and their latest slot is not in the future), with a
            <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ \App\Support\InternalTeamPlayReminder::DAYS_BETWEEN_REMINDERS }}-day</span>
            cooldown between automated sends. Members can unsubscribe from email; this page is your ops view across all players.
        </p>
    </div>

    <div class="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-end sm:justify-between">
        <div class="flex flex-wrap gap-3">
            <button
                type="button"
                wire:click="runScheduledReminders"
                wire:loading.attr="disabled"
                class="font-display inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold uppercase tracking-wide text-white shadow-sm transition hover:bg-emerald-700 disabled:opacity-60 dark:bg-emerald-500 dark:hover:bg-emerald-600"
            >
                <span wire:loading.remove wire:target="runScheduledReminders">Run scheduled job now</span>
                <span wire:loading wire:target="runScheduledReminders">Running…</span>
            </button>
        </div>
        <div class="w-full sm:w-auto sm:min-w-[14rem]">
            <label for="internal-play-filter" class="sr-only">Filter roster</label>
            <select
                id="internal-play-filter"
                wire:model.live="filter"
                class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-900 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
            >
                <option value="all">All members (players)</option>
                <option value="dormant_10">Dormant 10+ days (as booker)</option>
                <option value="eligible">Eligible for next auto send</option>
                <option value="unsubscribed">Unsubscribed</option>
                <option value="upcoming">Has upcoming booking (latest slot)</option>
                <option value="never_booked">No past bookings as booker</option>
            </select>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Members (players)</p>
            <p class="mt-1 font-display text-2xl font-bold text-zinc-900 dark:text-white">{{ $stats['members'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Dormant 10+ days</p>
            <p class="mt-1 font-display text-2xl font-bold text-amber-700 dark:text-amber-400">{{ $stats['dormant_10'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Eligible (auto)</p>
            <p class="mt-1 font-display text-2xl font-bold text-emerald-700 dark:text-emerald-400">{{ $stats['eligible'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Unsubscribed</p>
            <p class="mt-1 font-display text-2xl font-bold text-zinc-700 dark:text-zinc-300">{{ $stats['unsubscribed'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Never booked (as booker)</p>
            <p class="mt-1 font-display text-2xl font-bold text-zinc-900 dark:text-white">{{ $stats['never_booked'] }}</p>
        </div>
    </div>

    <div
        class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900"
    >
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-left text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:bg-zinc-950/80 dark:text-zinc-400">
                    <tr>
                        <th class="whitespace-nowrap px-4 py-3">Member</th>
                        <th class="whitespace-nowrap px-4 py-3">Last past booking</th>
                        <th class="whitespace-nowrap px-4 py-3">Days idle</th>
                        <th class="whitespace-nowrap px-4 py-3">Latest slot</th>
                        <th class="whitespace-nowrap px-4 py-3">Reminder</th>
                        <th class="whitespace-nowrap px-4 py-3">Unsub</th>
                        <th class="whitespace-nowrap px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($rows as $row)
                        @php($u = $row['user'])
                        <tr wire:key="internal-play-{{ $u->id }}" class="text-zinc-800 dark:text-zinc-200">
                            <td class="px-4 py-3">
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $u->name }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $u->email }}</p>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                @if ($row['last_past_booking_starts_at'])
                                    {{ $row['last_past_booking_starts_at']->timezone($tz)->format('M j, Y g:i a') }}
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($row['days_since_last_past'] !== null)
                                    <span
                                        @class([
                                            'font-semibold',
                                            $row['dormant_10_plus']
                                                ? 'text-amber-700 dark:text-amber-400'
                                                : 'text-zinc-700 dark:text-zinc-300',
                                        ])
                                    >
                                        {{ $row['days_since_last_past'] }}d
                                    </span>
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-400">
                                @if ($row['latest_booking_starts_at'])
                                    {{ $row['latest_booking_starts_at']->timezone($tz)->format('M j, Y g:i a') }}
                                    @if ($row['latest_is_upcoming'])
                                        <span
                                            class="ml-1 rounded-md bg-sky-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-sky-800 dark:bg-sky-950/60 dark:text-sky-200"
                                        >
                                            Upcoming
                                        </span>
                                    @endif
                                @else
                                    <span class="text-zinc-400">No bookings</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @if ($row['last_reminder_sent_at'])
                                    <span class="text-zinc-600 dark:text-zinc-400">
                                        {{ $row['last_reminder_sent_at']->timezone($tz)->format('M j, Y g:i a') }}
                                    </span>
                                    @if ($row['next_scheduled_window_at'] && ! $row['unsubscribed'])
                                        <p class="mt-1 text-[11px] text-zinc-500">
                                            Next auto window after
                                            {{ $row['next_scheduled_window_at']->timezone($tz)->format('M j, g:i a') }}
                                        </p>
                                    @endif
                                @else
                                    <span class="text-zinc-400">Never</span>
                                @endif
                                @if ($row['eligible_for_scheduled_reminder'])
                                    <p class="mt-1">
                                        <span
                                            class="inline-flex rounded-md bg-emerald-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-200"
                                        >
                                            Eligible now
                                        </span>
                                    </p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($row['unsubscribed'])
                                    <span class="text-amber-700 dark:text-amber-400">Yes</span>
                                @else
                                    <span class="text-zinc-400">No</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <a
                                        href="{{ route('admin.users.summary', $u) }}"
                                        wire:navigate
                                        class="rounded-lg border border-zinc-200 px-2.5 py-1 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                    >
                                        Summary
                                    </a>
                                    <a
                                        href="{{ route('admin.users.edit', $u) }}"
                                        wire:navigate
                                        class="rounded-lg border border-zinc-200 px-2.5 py-1 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                    >
                                        Edit user
                                    </a>
                                    <button
                                        type="button"
                                        wire:click="sendReminderNow('{{ $u->id }}')"
                                        wire:loading.attr="disabled"
                                        @if ($row['unsubscribed']) disabled @endif
                                        class="rounded-lg bg-zinc-900 px-2.5 py-1 text-xs font-semibold text-white hover:bg-zinc-800 disabled:cursor-not-allowed disabled:opacity-40 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white"
                                    >
                                        Send now
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-zinc-500">
                                No rows match this filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
