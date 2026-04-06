@php
    use App\Models\Booking;
    use App\Support\Money;

    $tz = config('app.timezone', 'UTC');
@endphp

<div class="space-y-8">
    <section
        class="relative overflow-hidden rounded-3xl border border-violet-200/80 bg-gradient-to-br from-violet-600 via-indigo-600 to-sky-600 p-6 text-white shadow-xl shadow-indigo-900/25 dark:border-violet-900/40 dark:from-violet-900 dark:via-indigo-900 dark:to-sky-950 sm:p-8"
    >
        <div
            class="pointer-events-none absolute -right-8 -top-8 size-40 rounded-full bg-white/10 blur-2xl"
            aria-hidden="true"
        ></div>
        <p class="relative text-sm font-bold uppercase tracking-widest text-violet-100/90">Coaching</p>
        <h1 class="relative mt-2 font-display text-3xl font-extrabold tracking-tight sm:text-4xl">
            Your coaching hub
        </h1>
        <p class="relative mt-3 max-w-xl text-base font-medium leading-relaxed text-violet-50">
            Players book courts and can add you in one request. Set which courts you cover, mark when you’re free, and
            keep your rate up to date.
        </p>
        <div class="relative mt-6 flex flex-wrap gap-3">
            <a
                href="{{ route('account.coach.courts') }}"
                wire:navigate
                class="inline-flex items-center rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-violet-800 shadow-md transition hover:bg-violet-50"
            >
                Venues you coach
            </a>
            <a
                href="{{ route('account.coach.availability') }}"
                wire:navigate
                class="inline-flex items-center rounded-xl border-2 border-white/40 bg-white/10 px-4 py-2.5 text-sm font-bold text-white backdrop-blur-sm transition hover:bg-white/20"
            >
                Set availability
            </a>
            <a
                href="{{ route('account.coach.profile') }}"
                wire:navigate
                class="inline-flex items-center rounded-xl bg-amber-300 px-4 py-2.5 text-sm font-bold text-amber-950 shadow-md transition hover:bg-amber-200 dark:bg-amber-400 dark:text-amber-950 dark:hover:bg-amber-300"
            >
                Coach profile & rate
            </a>
        </div>
    </section>

    <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80">
        <div class="flex items-center justify-between gap-2">
            <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Upcoming sessions (as coach)</h2>
        </div>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            Court bookings that include you — same flow as regular play, with coaching added.
        </p>
        <ul class="mt-5 space-y-3">
            @forelse ($this->upcomingCoachedSessions as $b)
                <li
                    class="flex flex-col gap-1 rounded-xl border border-zinc-100 bg-zinc-50/80 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-950/50 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <p class="font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $b->courtClient?->name ?? 'Venue' }}
                            —
                            {{ $b->court?->name ?? 'Court' }}
                        </p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $b->starts_at->timezone($tz)->format('D, M j') }}
                            ·
                            {{ $b->starts_at->timezone($tz)->format('g:i A') }}
                            –
                            {{ $b->ends_at->timezone($tz)->format('g:i A') }}
                            @if ($b->user)
                                · with {{ $b->user->name }}
                            @endif
                        </p>
                    </div>
                    <div class="text-right">
                        <span
                            class="inline-flex rounded-full bg-zinc-200/80 px-2.5 py-0.5 text-xs font-semibold text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200"
                        >
                            {{ Booking::statusDisplayLabel($b->status) }}
                        </span>
                        @if ($b->coach_fee_cents > 0)
                            <p class="mt-1 text-xs tabular-nums text-zinc-500 dark:text-zinc-400">
                                Coaching
                                {{ Money::formatMinor($b->coach_fee_cents, $b->currency ?? 'PHP') }}
                            </p>
                        @endif
                    </div>
                </li>
            @empty
                <li class="rounded-xl border border-dashed border-zinc-200 px-4 py-8 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    Nothing scheduled yet. When players pick you on a booking request, it will show up here.
                </li>
            @endforelse
        </ul>
    </section>
</div>
