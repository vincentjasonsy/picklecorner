@php
    use App\Support\Money;
@endphp

<div class="space-y-8">
    <div>
        <p class="text-sm font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">Book now</p>
        <h1 class="mt-2 font-display text-2xl font-extrabold tracking-tight text-zinc-900 dark:text-white sm:text-3xl">
            Find a court
        </h1>
        <p class="mt-3 max-w-2xl text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
            Browse partner venues below. Reach out to the club (front desk, phone, or their usual channels) to grab a
            slot — your bookings will show up under <strong>My games</strong> once they’re in the system.
        </p>
    </div>

    @if ($venues->isEmpty())
        <div
            class="rounded-2xl border border-dashed border-zinc-300 bg-white/80 p-10 text-center dark:border-zinc-700 dark:bg-zinc-900/60"
        >
            <p class="text-2xl" aria-hidden="true">🏟️</p>
            <p class="mt-3 font-medium text-zinc-800 dark:text-zinc-200">No venues listed yet</p>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Check back soon — new clubs join Pickle Corner all the time.
            </p>
        </div>
    @else
        <ul class="grid gap-4 sm:grid-cols-2">
            @foreach ($venues as $v)
                <li
                    wire:key="venue-{{ $v->id }}"
                    class="rounded-2xl border border-emerald-200/80 bg-white p-5 shadow-sm dark:border-emerald-900/40 dark:bg-zinc-900/80"
                >
                    <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">{{ $v->name }}</h2>
                    @if ($v->city)
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $v->city }}</p>
                    @endif
                    @if ($v->hourly_rate_cents)
                        <p class="mt-3 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            From about
                        </p>
                        <p class="mt-0.5 font-display text-base font-bold text-emerald-700 dark:text-emerald-400">
                            {{ Money::formatMinor($v->hourly_rate_cents, $v->currency) }}
                            <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">/ hr</span>
                        </p>
                    @endif
                    <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">
                        Contact this venue to book — they’ll set you up on the schedule.
                    </p>
                </li>
            @endforeach
        </ul>
    @endif
</div>
