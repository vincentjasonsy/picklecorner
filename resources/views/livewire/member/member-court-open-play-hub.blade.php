@php
    use App\Models\Booking;

    $tz = config('app.timezone', 'UTC');
@endphp

<div class="space-y-8">
    <div>
        <h1 class="font-display text-2xl font-bold text-zinc-900 dark:text-white">Court open play</h1>
        <p class="mt-1 max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
            When you book a court as <strong>open play</strong>, you can share a link so others can ask to join. Manage
            requests here, mark who’s paid, and drop players if needed.
        </p>
    </div>

    @if (session('status'))
        <div
            class="rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-medium text-teal-950 dark:border-teal-900/50 dark:bg-teal-950/40 dark:text-teal-100"
            role="status"
        >
            {{ session('status') }}
        </div>
    @endif

    <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80">
        <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">You’re hosting</h2>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Upcoming open-play bookings you created</p>
        <ul class="mt-5 space-y-3">
            @forelse ($hostedSessions as $b)
                <li
                    class="flex flex-col gap-3 rounded-xl border border-zinc-100 bg-zinc-50/80 px-4 py-4 dark:border-zinc-800 dark:bg-zinc-950/50 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <p class="font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $b->courtClient?->name ?? 'Venue' }}
                            · {{ $b->court?->name ?? 'Court' }}
                        </p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $b->starts_at?->timezone($tz)->format('D, M j · g:i A') }}
                            · {{ Booking::statusDisplayLabel($b->status) }}
                        </p>
                        <p class="mt-1 text-xs text-zinc-500">
                            Pending {{ $b->pending_participants_count }} · In
                            {{ $b->accepted_participants_count }}/{{ $b->open_play_max_slots ?? '—' }} slots
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a
                            href="{{ route('account.court-open-plays.join', $b) }}"
                            wire:navigate
                            class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs font-bold uppercase tracking-wide text-zinc-800 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
                        >
                            Join link
                        </a>
                        <a
                            href="{{ route('account.court-open-plays.host', $b) }}"
                            wire:navigate
                            class="rounded-lg bg-violet-600 px-3 py-2 text-xs font-bold uppercase tracking-wide text-white hover:bg-violet-500"
                        >
                            Manage
                        </a>
                    </div>
                </li>
            @empty
                <li class="rounded-xl border border-dashed border-zinc-200 py-10 text-center dark:border-zinc-700">
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">No open-play sessions yet.</p>
                    <p class="mt-1 text-xs text-zinc-500">
                        Book a court (single block) and turn on <strong>Open play</strong> at checkout.
                    </p>
                </li>
            @endforelse
        </ul>
    </section>
</div>
