@php
    $tz = config('app.timezone', 'UTC');
@endphp

<div class="space-y-6">
    <div>
        <h1 class="font-display text-2xl font-bold text-zinc-900 dark:text-white">Availability</h1>
        <p class="mt-2 max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
            Pick a venue, then toggle hours — we apply the same hours to every court at that venue you’ve turned on.
            Hours follow that venue’s weekly schedule (closed days show no slots).
        </p>
    </div>

    @if ($this->coachedVenues->isEmpty())
        <div
            class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100"
            role="status"
        >
            Turn on at least one venue under
            <a href="{{ route('account.coach.courts') }}" wire:navigate class="font-bold underline">Venues you coach</a>
            before setting availability.
        </div>
    @else
        <div class="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-end">
            <div class="max-w-xs">
                <label
                    class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                    for="coach-avail-venue"
                >
                    Venue
                </label>
                <select
                    id="coach-avail-venue"
                    wire:model.live="courtClientId"
                    class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                >
                    @foreach ($this->coachedVenues as $v)
                        <option value="{{ $v->id }}">
                            {{ $v->name }}
                            @if ($v->city)
                                — {{ $v->city }}
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="max-w-xs">
                <label
                    class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                    for="coach-avail-date"
                >
                    Date
                </label>
                <input
                    id="coach-avail-date"
                    type="date"
                    wire:model.live="availabilityDate"
                    class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                />
            </div>
        </div>

        @if ($this->slotHoursForDate === [])
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                This venue is closed on that day or has no bookable start hours — pick another date.
            </p>
        @else
            @php
                $hours = $this->slotHoursForDate;
                $on = $this->availableHourLookup;
            @endphp
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900/80">
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                    Tap hours you’re available to coach (all courts at this venue)
                </p>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($hours as $h)
                        <button
                            type="button"
                            wire:click="toggleHour({{ $h }})"
                            wire:key="coach-h-{{ $courtClientId }}-{{ $availabilityDate }}-{{ $h }}"
                            @class([
                                'rounded-lg border px-3 py-2 text-sm font-semibold transition',
                                'border-violet-500 bg-violet-500 text-white shadow-sm dark:border-violet-400 dark:bg-violet-600' => isset($on[$h]),
                                'border-zinc-200 bg-zinc-50 text-zinc-700 hover:border-violet-300 hover:bg-violet-50 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-200 dark:hover:border-violet-700' => ! isset($on[$h]),
                            ])
                        >
                            {{ \Carbon\Carbon::createFromTime($h, 0, 0, $tz)->format('g A') }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</div>
