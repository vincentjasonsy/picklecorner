@php
    use App\Models\Booking;
    use App\Support\Money;

    $tz = config('app.timezone', 'UTC');
@endphp

<div class="space-y-8">
    <section
        class="relative overflow-hidden rounded-3xl border border-emerald-200/80 bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-600 p-6 text-white shadow-xl shadow-emerald-900/20 dark:border-emerald-800/50 dark:from-emerald-800 dark:via-teal-800 dark:to-cyan-900 sm:p-8"
    >
        <div
            class="pointer-events-none absolute -right-8 -top-8 size-40 rounded-full bg-white/10 blur-2xl"
            aria-hidden="true"
        ></div>
        <div
            class="pointer-events-none absolute -bottom-10 -left-10 size-48 rounded-full bg-black/10 blur-2xl"
            aria-hidden="true"
        ></div>
        <p class="relative text-sm font-bold uppercase tracking-widest text-emerald-100/90">Game on</p>
        <h1 class="relative mt-2 font-display text-3xl font-extrabold tracking-tight sm:text-4xl">
            Hey, {{ $this->firstName }}!
        </h1>
        <p class="relative mt-3 max-w-xl text-base font-medium leading-relaxed text-emerald-50">
            This is your home court — track what’s coming up, relive recent matches, and tweak your profile whenever
            you want. Now go get that dink dialed in.
        </p>
        <div class="relative mt-6 flex flex-wrap gap-3">
            <a
                href="{{ route('account.bookings') }}"
                wire:navigate
                class="inline-flex items-center rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-emerald-800 shadow-md transition hover:bg-emerald-50"
            >
                Full match log
            </a>
            <a
                href="{{ route('account.settings') }}"
                wire:navigate
                class="inline-flex items-center rounded-xl border-2 border-white/40 bg-white/10 px-4 py-2.5 text-sm font-bold text-white backdrop-blur-sm transition hover:bg-white/20"
            >
                Profile & gear
            </a>
            <a
                href="{{ route('account.book') }}"
                wire:navigate
                class="inline-flex items-center rounded-xl bg-amber-300 px-4 py-2.5 text-sm font-bold text-amber-950 shadow-md transition hover:bg-amber-200 dark:bg-amber-400 dark:text-amber-950 dark:hover:bg-amber-300"
            >
                Book now
            </a>
        </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-3">
        <div
            class="rounded-2xl border border-emerald-200/70 bg-white/90 p-5 shadow-sm dark:border-emerald-900/40 dark:bg-zinc-900/80"
        >
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Up next</p>
            <p class="mt-2 font-display text-3xl font-extrabold text-zinc-900 dark:text-white">
                {{ $this->stats['upcoming'] }}
            </p>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Courts on your calendar</p>
        </div>
        <div
            class="rounded-2xl border border-teal-200/70 bg-white/90 p-5 shadow-sm dark:border-teal-900/40 dark:bg-zinc-900/80"
        >
            <p class="text-xs font-bold uppercase tracking-wider text-teal-600 dark:text-teal-400">In the books</p>
            <p class="mt-2 font-display text-3xl font-extrabold text-zinc-900 dark:text-white">
                {{ $this->stats['played'] }}
            </p>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Past sessions (incl. confirmed)</p>
        </div>
        <div
            class="rounded-2xl border border-cyan-200/70 bg-white/90 p-5 shadow-sm dark:border-cyan-900/40 dark:bg-zinc-900/80"
        >
            <p class="text-xs font-bold uppercase tracking-wider text-cyan-600 dark:text-cyan-400">Finished strong</p>
            <p class="mt-2 font-display text-3xl font-extrabold text-zinc-900 dark:text-white">
                {{ $this->stats['wins_on_the_board'] }}
            </p>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Completed games</p>
        </div>
    </section>

    <div class="grid gap-8 lg:grid-cols-2">
        <section
            class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80"
        >
            <div class="flex items-center justify-between gap-2">
                <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Coming up</h2>
                <x-icon name="calendar" class="size-7 text-emerald-600 dark:text-emerald-400" />
            </div>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Your next blocks of court time</p>
            <ul class="mt-5 space-y-3">
                @forelse ($this->upcomingBookings as $b)
                    <li
                        class="flex flex-col gap-1 rounded-xl border border-zinc-100 bg-zinc-50/80 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-950/50 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <p class="font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $b->courtClient?->name ?? 'Venue' }}
                            </p>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $b->court?->name ?? 'Court' }}
                                ·
                                {{ $b->starts_at?->timezone($tz)->format('D, M j') }}
                                ·
                                {{ $b->starts_at?->timezone($tz)->format('g:i A') }}
                            </p>
                        </div>
                        <span
                            class="shrink-0 self-start rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-200"
                        >
                            {{ Booking::statusDisplayLabel($b->status) }}
                        </span>
                    </li>
                @empty
                    <li class="rounded-xl border border-dashed border-zinc-200 py-10 text-center dark:border-zinc-700">
                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">No upcoming games yet.</p>
                        <p class="mt-1 text-xs text-zinc-500">Book with your favorite venue and it’ll show up here!</p>
                    </li>
                @endforelse
            </ul>
        </section>

        <section
            class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80"
        >
            <div class="flex items-center justify-between gap-2">
                <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Recently played</h2>
                <x-icon name="clock" class="size-7 text-emerald-600 dark:text-emerald-400" />
            </div>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">A quick look back at the action</p>
            <ul class="mt-5 space-y-3">
                @forelse ($this->recentBookings as $b)
                    <li
                        class="rounded-xl border border-zinc-100 bg-zinc-50/80 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-950/50"
                    >
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <p class="font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $b->courtClient?->name ?? 'Venue' }}
                            </p>
                            @if ($b->amount_cents !== null)
                                <p class="text-sm font-bold text-emerald-700 dark:text-emerald-400">
                                    {{ Money::formatMinor($b->amount_cents, $b->currency) }}
                                </p>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $b->starts_at?->timezone($tz)->format('M j, Y') }}
                            ·
                            {{ Booking::statusDisplayLabel($b->status) }}
                        </p>
                    </li>
                @empty
                    <li class="rounded-xl border border-dashed border-zinc-200 py-10 text-center dark:border-zinc-700">
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Your match history will land here.</p>
                    </li>
                @endforelse
            </ul>
            <a
                href="{{ route('account.bookings') }}"
                wire:navigate
                class="mt-5 inline-flex text-sm font-bold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300"
            >
                See everything →
            </a>
        </section>
    </div>
</div>
