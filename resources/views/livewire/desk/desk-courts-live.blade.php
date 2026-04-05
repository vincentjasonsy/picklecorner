@php
    $cc = $this->courtClient;
    $rows = $this->courtsLiveRows;
    $tz = config('app.timezone', 'UTC');
@endphp

<div class="space-y-6" wire:poll.45s>
    <a
        href="{{ route('desk.home') }}"
        wire:navigate
        class="inline-block text-sm font-medium text-teal-700 hover:text-teal-800 dark:text-teal-400 dark:hover:text-teal-300"
    >
        ← Front desk home
    </a>

    @if (! $cc)
        <p class="text-sm text-red-600 dark:text-red-400">No venue is assigned to your desk account.</p>
    @elseif ($rows->isEmpty())
        <p class="text-sm text-stone-600 dark:text-stone-400">
            Add courts on the venue side to see who is on each court and who is up next.
        </p>
    @else
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="font-display text-xs font-bold uppercase tracking-wider text-teal-700 dark:text-teal-400">
                    Right now
                </p>
                <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                    One card per court — <strong>on court</strong> is the booking happening now;
                    <strong>up next</strong> starts when that block ends (or the next future slot if the court is open).
                </p>
            </div>
            <p class="text-xs text-stone-500 dark:text-stone-500">
                Refreshes every 45s · {{ now()->timezone($tz)->isoFormat('h:mm:ss A') }}
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($rows as $row)
                @php
                    /** @var \App\Models\Court $court */
                    $court = $row['court'];
                    /** @var \App\Models\Booking|null $cur */
                    $cur = $row['current'];
                    /** @var \App\Models\Booking|null $nxt */
                    $nxt = $row['next'];
                @endphp
                <article
                    wire:key="desk-live-{{ $court->id }}"
                    class="flex flex-col overflow-hidden rounded-2xl border-2 border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-900"
                >
                    <header
                        class="flex shrink-0 items-center gap-3 border-b border-stone-200 bg-gradient-to-r from-stone-50 to-teal-50/50 px-4 py-3 dark:border-stone-700 dark:from-stone-800 dark:to-teal-950/40"
                    >
                        <div
                            class="h-12 w-20 shrink-0 overflow-hidden rounded-lg border border-stone-200 bg-stone-100 dark:border-stone-600 dark:bg-stone-900"
                        >
                            <img
                                src="{{ $court->staticImageUrl() }}"
                                alt="{{ $court->name }}"
                                class="size-full object-cover object-center"
                                loading="lazy"
                            />
                        </div>
                        <h3 class="font-display text-base font-bold leading-tight text-stone-900 dark:text-white">
                            {{ $court->name }}
                        </h3>
                    </header>
                    <div class="flex flex-1 flex-col gap-4 p-4">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-teal-700 dark:text-teal-400">
                                On court now
                            </p>
                            @if ($cur)
                                <p class="mt-1 font-display text-lg font-bold leading-tight text-stone-900 dark:text-white">
                                    {{ $cur->user?->name ?? 'Guest' }}
                                </p>
                                <p class="mt-1 text-xs text-stone-600 dark:text-stone-400">
                                    Until
                                    {{ $cur->ends_at->timezone($tz)->isoFormat('h:mm A') }}
                                    ·
                                    {{ \App\Models\Booking::statusDisplayLabel($cur->status) }}
                                </p>
                            @else
                                <p class="mt-2 text-sm font-medium text-stone-500 dark:text-stone-400">
                                    Open — no active booking
                                </p>
                            @endif
                        </div>
                        <div class="border-t border-dashed border-stone-200 pt-3 dark:border-stone-700">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                Up next
                            </p>
                            @if ($nxt)
                                <p class="mt-1 font-display text-lg font-bold leading-tight text-stone-900 dark:text-white">
                                    {{ $nxt->user?->name ?? 'Guest' }}
                                </p>
                                <p class="mt-1 text-xs text-stone-600 dark:text-stone-400">
                                    Starts
                                    {{ $nxt->starts_at->timezone($tz)->isoFormat('MMM D, h:mm A') }}
                                    ·
                                    {{ \App\Models\Booking::statusDisplayLabel($nxt->status) }}
                                </p>
                            @else
                                <p class="mt-2 text-sm font-medium text-stone-500 dark:text-stone-400">
                                    No upcoming reservation
                                </p>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <p class="text-xs text-stone-500 dark:text-stone-500">
            Pending, confirmed, and completed reservations count toward the schedule. Cancelled or denied bookings are
            omitted.
        </p>
    @endif
</div>
