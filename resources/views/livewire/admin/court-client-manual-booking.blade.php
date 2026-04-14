<div class="w-full space-y-8">
    @if (session('status'))
        <div
            @class([
                'rounded-xl border px-4 py-3 text-sm font-medium',
                $this->manualBookingPortal() === 'desk'
                    ? 'border-teal-200 bg-teal-50 text-teal-950 dark:border-teal-900/50 dark:bg-teal-950/40 dark:text-teal-100'
                    : 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-100',
            ])
            role="status"
        >
            {{ session('status') }}
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3">
        <a
            href="{{ $this->manualBookingBackUrl() }}"
            wire:navigate
            @class([
                'text-sm font-medium',
                $this->manualBookingPortal() === 'desk'
                    ? 'text-teal-700 hover:text-teal-800 dark:text-teal-400 dark:hover:text-teal-300'
                    : 'text-emerald-600 hover:text-emerald-700 dark:text-emerald-400',
            ])
        >
            @if ($this->manualBookingPortal() === 'desk')
                ← Front desk home
            @elseif ($this->manualBookingPortal() === 'venue')
                ← Settings &amp; schedule
            @else
                ← Back to {{ $courtClient->name }}
            @endif
        </a>
    </div>

    <div>
        <h1 class="font-display text-2xl font-bold text-stone-900 dark:text-white">
            @if ($this->manualBookingPortal() === 'desk')
                New booking request
            @else
                Manual booking
            @endif
        </h1>
        <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
            @if ($this->manualBookingPortal() === 'desk')
                Walk-in or phone: choose court and time for the guest. The venue decides how requests are approved
                (manual, auto-confirm, or auto-decline). Optional payment notes help the office; gift cards apply only
                after confirmation.
            @else
                Create confirmed booking(s) for a player or coach. Use the same grid as availability: each column is a court.
                Select one or more cells; on each court, hours must form consecutive blocks (gaps become separate bookings).
                Pricing follows slot rates per hour. Payment and optional gift card apply to the combined total.
            @endif
        </p>
    </div>

        @php
            $manualCourts = $this->courtsOrderedForGrid();
            $manualSlotHours = $this->slotHoursForSelectedDate();
            $manualBookingDow = $this->bookingDayOfWeek();
            $manualDateBlocks = $this->manualBookingDateBlockLookup;
            $manualOccupancy = $this->manualBookingOccupancyBySlot;
        @endphp

    <div
        class="overflow-hidden rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900"
    >
        <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Courts &amp; time</h2>

        @if ($manualCourts->isEmpty())
            <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">
                Add at least one court on the venue edit page before creating manual bookings.
            </p>
        @else
            <div class="mt-4 space-y-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Date
                    </p>
                    <div
                        class="mt-2 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:gap-4"
                    >
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                wire:click="shiftBookingDate(-1)"
                                class="rounded-lg border border-zinc-200 px-3 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                            >
                                Previous day
                            </button>
                            <input
                                type="date"
                                wire:model.live="bookingCalendarDate"
                                class="rounded-lg border border-zinc-200 px-3 py-2 text-sm text-zinc-800 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                            />
                            <button
                                type="button"
                                wire:click="shiftBookingDate(1)"
                                class="rounded-lg border border-zinc-200 px-3 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                            >
                                Next day
                            </button>
                        </div>
                        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ $this->bookingCalendarDateLabel() }}
                            <span class="text-zinc-500">({{ $dayLabels[$manualBookingDow] ?? '' }})</span>
                        </p>
                    </div>
                    @error('bookingCalendarDate')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                @if (count($manualSlotHours) === 0)
                    <p
                        class="rounded-lg border border-dashed border-zinc-300 px-4 py-6 text-center text-sm text-zinc-500 dark:border-zinc-600"
                    >
                        @if ($this->isBookingDateVenueClosure())
                            This calendar day is a <strong class="font-semibold text-zinc-700 dark:text-zinc-300">whole-venue closed day</strong>.
                            There are no bookable hours — change the date or remove the closure under venue schedule settings.
                        @else
                            This date is closed or has no bookable hourly window. Pick another date or adjust venue hours.
                        @endif
                    </p>
                @else
                    <div>
                        <div class="flex flex-wrap items-end justify-between gap-2">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    Availability grid — tap to select
                                </p>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                    @if ($this->manualBookingPortal() === 'desk')
                                        Green = open; slate = booked — <strong>tap a booked cell</strong> for full
                                        details. Red = blocked (not selectable). Strong green = your selection for a new
                                        request.
                                    @else
                                        Green = open; slate = already booked (shows guest). Red = blocked (venue staff may
                                        still select to override). Strong green = your selection. Booked cells cannot be
                                        selected.
                                    @endif
                                </p>
                            </div>
                            @if (count($this->selectedManualSlots) > 0)
                                <button
                                    type="button"
                                    wire:click="clearSlotSelection"
                                    class="text-xs font-semibold text-zinc-600 underline decoration-zinc-300 underline-offset-2 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
                                >
                                    Clear selection
                                </button>
                            @endif
                        </div>
                        <div class="mt-3 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800">
                            <div class="overflow-x-auto">
                            <table class="min-w-full border-collapse text-left text-sm">
                                <thead>
                                    <tr
                                        class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/80"
                                    >
                                        <th
                                            class="sticky left-0 z-10 bg-zinc-50 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:bg-zinc-800/80 dark:text-zinc-400"
                                        >
                                            Time
                                        </th>
                                        @foreach ($manualCourts as $court)
                                            <th
                                                class="min-w-[7.5rem] px-2 py-2 text-center text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-300"
                                            >
                                                {{ $court->name }}
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                    @foreach ($manualSlotHours as $hour)
                                        <tr wire:key="mb-grid-{{ $bookingCalendarDate }}-{{ $hour }}">
                                            <td
                                                class="sticky left-0 z-10 whitespace-nowrap bg-white px-3 py-2 text-xs font-medium text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300"
                                            >
                                                {{ $this->slotHourLabel($hour) }}
                                            </td>
                                            @foreach ($manualCourts as $court)
                                                @php
                                                    $slotKey = $court->id.'-'.$hour;
                                                    $booked = $manualOccupancy[$slotKey] ?? null;
                                                    $weeklyBlocked = $court->isWeeklySlotBlocked(
                                                        $manualBookingDow,
                                                        $hour,
                                                    );
                                                    $dateBlocked = isset($manualDateBlocks[$slotKey]);
                                                    $blocked = $weeklyBlocked || $dateBlocked;
                                                    $slotSelected = $this->isManualSlotSelected($court->id, $hour);
                                                    if ($booked !== null) {
                                                        $availStyle =
                                                            $this->manualBookingPortal() === 'desk'
                                                                ? 'cursor-pointer border-slate-300 bg-slate-200/95 text-slate-900 hover:border-teal-500 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:border-teal-500/60 dark:hover:bg-slate-700/90'
                                                                : 'cursor-default border-slate-300 bg-slate-200/95 text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100';
                                                        $cellTitle = 'Booked';
                                                        $cellSub = $booked['name'];
                                                        $cellMeta = $booked['status'];
                                                        $isBookedCell = true;
                                                    } elseif ($slotSelected) {
                                                        $availStyle =
                                                            'border-emerald-600 bg-emerald-600 text-white shadow-sm ring-2 ring-emerald-400/40 dark:ring-emerald-500/30';
                                                        $cellTitle = 'Selected';
                                                        $cellSub = null;
                                                        $cellMeta = null;
                                                        $isBookedCell = false;
                                                    } elseif ($blocked) {
                                                        $availStyle =
                                                            'border-red-200 bg-red-50/90 dark:border-red-900/50 dark:bg-red-950/25';
                                                        if ($weeklyBlocked && $dateBlocked) {
                                                            $cellTitle = 'Blocked';
                                                        } elseif ($dateBlocked) {
                                                            $cellTitle = 'Date block';
                                                        } elseif ($weeklyBlocked) {
                                                            $cellTitle = 'Weekly block';
                                                        } else {
                                                            $cellTitle = 'Blocked';
                                                        }
                                                        $cellSub = null;
                                                        $cellMeta = null;
                                                        $isBookedCell = false;
                                                    } else {
                                                        $availStyle =
                                                            'border-emerald-200 bg-emerald-50/50 dark:border-emerald-900/40 dark:bg-emerald-950/20';
                                                        $cellTitle = 'Open';
                                                        $cellSub = null;
                                                        $cellMeta = null;
                                                        $isBookedCell = false;
                                                    }
                                                @endphp
                                                <td class="p-1.5 align-middle">
                                                    @if ($isBookedCell && $this->manualBookingPortal() === 'desk')
                                                        <button
                                                            type="button"
                                                            wire:click="openDeskBookedSlot('{{ $court->id }}', {{ $hour }})"
                                                            title="View booking details"
                                                            class="flex min-h-[3.25rem] w-full flex-col items-center justify-center rounded-lg border px-1.5 py-1.5 text-center transition-colors {{ $availStyle }}"
                                                        >
                                                            <span class="text-[10px] font-bold uppercase tracking-wide">
                                                                Booked
                                                            </span>
                                                            <span class="mt-0.5 line-clamp-2 max-w-full text-[10px] font-semibold leading-tight">
                                                                {{ $cellSub }}
                                                            </span>
                                                            <span class="mt-0.5 text-[9px] font-medium opacity-80">
                                                                {{ $cellMeta }}
                                                            </span>
                                                        </button>
                                                    @elseif ($isBookedCell)
                                                        <div
                                                            title="{{ $cellSub }} · {{ $cellMeta }}"
                                                            class="flex min-h-[3.25rem] w-full flex-col items-center justify-center rounded-lg border px-1.5 py-1.5 text-center {{ $availStyle }}"
                                                        >
                                                            <span class="text-[10px] font-bold uppercase tracking-wide">
                                                                Booked
                                                            </span>
                                                            <span class="mt-0.5 line-clamp-2 max-w-full text-[10px] font-semibold leading-tight">
                                                                {{ $cellSub }}
                                                            </span>
                                                            <span class="mt-0.5 text-[9px] font-medium opacity-80">
                                                                {{ $cellMeta }}
                                                            </span>
                                                        </div>
                                                    @elseif ($blocked && ! $this->manualBookingMaySelectBlockedSlots())
                                                        <div
                                                            title="Not available — blocked"
                                                            class="flex min-h-[3.25rem] w-full cursor-default flex-col items-center justify-center rounded-lg border px-2 py-2 text-center text-xs font-semibold {{ $availStyle }}"
                                                        >
                                                            {{ $cellTitle }}
                                                        </div>
                                                    @else
                                                        <button
                                                            type="button"
                                                            wire:click="toggleManualSlot('{{ $court->id }}', {{ $hour }})"
                                                            class="flex min-h-[3.25rem] w-full flex-col items-center justify-center rounded-lg border px-2 py-2 text-center text-xs font-semibold transition-colors hover:border-emerald-500/60 hover:bg-emerald-50/80 dark:hover:bg-emerald-950/30 {{ $availStyle }}"
                                                        >
                                                            {{ $cellTitle }}
                                                        </button>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            </div>
                        </div>
                        @error('selectedManualSlots')
                            <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            </div>
        @endif
    </div>

    @if ($manualCourts->isNotEmpty())
        @php
            $manualUserHits = $this->manualBookingUserResults;
            $manualUserPicked = $this->selectedManualBookingUser;
        @endphp

        <div
            class="overflow-hidden rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900"
        >
            <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">
                @if ($this->manualBookingPortal() === 'desk')
                    Guest &amp; details
                @else
                    Guest &amp; payment
                @endif
            </h2>

            <div class="mt-4 space-y-4">
                <div class="relative">
                    <label
                        class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                    >
                        Player or coach
                    </label>
                    @if ($manualUserPicked)
                        <div
                            class="mt-1 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-emerald-200 bg-emerald-50/80 px-3 py-2 dark:border-emerald-900/50 dark:bg-emerald-950/30"
                        >
                            <div class="min-w-0 text-sm">
                                <p class="truncate font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $manualUserPicked->name }}
                                </p>
                                <p class="truncate text-xs text-zinc-600 dark:text-zinc-400">
                                    {{ $manualUserPicked->email }}
                                </p>
                            </div>
                            <button
                                type="button"
                                wire:click="clearManualBookingUserSelection"
                                class="shrink-0 text-xs font-semibold text-emerald-700 hover:text-emerald-900 dark:text-emerald-300"
                            >
                                Change
                            </button>
                        </div>
                    @else
                        <input
                            type="search"
                            wire:model.live.debounce.300ms="manualBookingUserSearch"
                            class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                            placeholder="Type email or name (min. 2 characters)"
                            autocomplete="off"
                        />
                        @if ($manualUserHits->isNotEmpty())
                            <ul
                                class="absolute z-20 mt-1 max-h-52 w-full overflow-auto rounded-lg border border-zinc-200 bg-white py-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
                                role="listbox"
                            >
                                @foreach ($manualUserHits as $u)
                                    <li wire:key="mb-user-{{ $u->id }}">
                                        <button
                                            type="button"
                                            wire:click="selectManualBookingUser('{{ $u->id }}')"
                                            class="flex w-full flex-col items-start px-3 py-2 text-left text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800"
                                        >
                                            <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                                {{ $u->name }}
                                            </span>
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $u->email }}</span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    @endif
                    @error('manualBookingUserId')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div
                    class="rounded-lg border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700 dark:bg-zinc-900/40"
                >
                    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        @if ($this->manualBookingPortal() === 'desk')
                            Payment (optional)
                        @else
                            Payment
                        @endif
                    </p>
                    @if ($this->manualBookingPortal() === 'desk')
                        <p class="mt-2 text-xs text-zinc-600 dark:text-zinc-400">
                            Add if the customer already paid; the venue admin can still approve or deny the request.
                        </p>
                    @endif
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div class="sm:col-span-2 sm:max-w-xs">
                            <label
                                class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                            >
                                Method
                            </label>
                            <select
                                wire:model="manualBookingPaymentMethod"
                                class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                            >
                                @foreach (\App\Models\Booking::paymentMethodOptions() as $pm)
                                    <option value="{{ $pm }}">
                                        {{ \App\Models\Booking::paymentMethodLabel($pm) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('manualBookingPaymentMethod')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <x-gcash-payment-hint :method="$manualBookingPaymentMethod" />
                        </div>
                        <div class="sm:col-span-2">
                            <label
                                class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                            >
                                Reference number
                                @if ($this->manualBookingPortal() === 'desk')
                                    <span class="font-normal normal-case text-zinc-400">(optional)</span>
                                @endif
                            </label>
                            <input
                                type="text"
                                wire:model="manualBookingPaymentReference"
                                class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 font-mono text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                placeholder="e.g. GCash ref / transaction ID"
                                autocomplete="off"
                            />
                            @error('manualBookingPaymentReference')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label
                                class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                            >
                                Proof screenshot (optional)
                            </label>
                            <input
                                type="file"
                                wire:model="manualBookingPaymentProof"
                                accept="image/jpeg,image/png,image/webp,image/gif"
                                class="mt-1 block w-full text-sm text-zinc-600 file:mr-3 file:rounded-lg file:border-0 file:bg-zinc-200 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-zinc-800 hover:file:bg-zinc-300 dark:text-zinc-400 dark:file:bg-zinc-700 dark:file:text-zinc-100 dark:hover:file:bg-zinc-600"
                            />
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                JPEG, PNG, WebP, or GIF · max 5 MB. Stored under
                                <code class="rounded bg-zinc-200/80 px-1 dark:bg-zinc-800">storage/app/public</code>
                                — run
                                <code class="rounded bg-zinc-200/80 px-1 dark:bg-zinc-800">php artisan storage:link</code>
                                for public URLs.
                            </p>
                            <div
                                wire:loading
                                wire:target="manualBookingPaymentProof"
                                class="mt-1 text-xs text-zinc-500"
                            >
                                Uploading…
                            </div>
                            @error('manualBookingPaymentProof')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div>
                    <label
                        class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                    >
                        Notes (optional)
                    </label>
                    <textarea
                        wire:model="manualBookingNotes"
                        rows="2"
                        class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                    ></textarea>
                    @error('manualBookingNotes')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                @if ($this->manualBookingPortal() !== 'desk')
                    <div>
                        <label
                            class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                        >
                            Gift card code (optional)
                        </label>
                        <input
                            type="text"
                            wire:model="manualBookingGiftCardCode"
                            class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 font-mono text-sm uppercase dark:border-zinc-700 dark:bg-zinc-950"
                            placeholder="GIFT-…"
                            autocomplete="off"
                        />
                        @error('manualBookingGiftCardCode')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            </div>

            <div class="mt-6 flex justify-end">
                <button
                    type="button"
                    wire:click="saveManualBooking"
                    @class([
                        'rounded-xl px-6 py-3 text-sm font-bold uppercase tracking-wide text-white shadow-md transition-colors',
                        $this->manualBookingPortal() === 'desk'
                            ? 'bg-teal-600 shadow-teal-900/25 hover:bg-teal-700 dark:bg-teal-600 dark:hover:bg-teal-500'
                            : 'bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-500',
                    ])
                >
                    @if ($this->manualBookingPortal() === 'desk')
                        Submit request
                    @else
                        Create booking(s)
                    @endif
                </button>
            </div>
        </div>
    @endif

    @if ($this->manualBookingPortal() === 'desk' && $this->deskViewBookingId)
        @php
            $dv = $this->deskViewBooking;
            $dvTz = config('app.timezone', 'UTC');
        @endphp
        <div
            class="fixed inset-0 z-50 flex items-end justify-center bg-stone-950/60 p-4 backdrop-blur-sm sm:items-center"
            wire:click.self="closeDeskViewBooking"
            wire:keydown.escape.window="closeDeskViewBooking"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            aria-labelledby="desk-booking-detail-title"
        >
            @if ($dv)
                <div
                    class="flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-2xl dark:border-stone-700 dark:bg-stone-900"
                    wire:click.stop
                >
                    <div
                        class="flex shrink-0 items-start justify-between gap-3 border-b border-stone-200 px-5 py-4 dark:border-stone-700"
                    >
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-wider text-teal-700 dark:text-teal-400">
                                Booking details
                            </p>
                            <h3
                                id="desk-booking-detail-title"
                                class="font-display mt-1 truncate text-lg font-bold text-stone-900 dark:text-white"
                            >
                                {{ $dv->user?->name ?? 'Guest' }}
                            </h3>
                        </div>
                        <button
                            type="button"
                            wire:click="closeDeskViewBooking"
                            class="shrink-0 rounded-lg border border-stone-200 px-3 py-1.5 text-sm font-semibold text-stone-700 hover:bg-stone-50 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-800"
                        >
                            Close
                        </button>
                    </div>
                    <div class="min-h-0 flex-1 overflow-y-auto">
                    <dl class="space-y-4 px-5 py-5 text-sm">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                Status
                            </dt>
                            <dd class="mt-1 font-medium text-stone-900 dark:text-stone-100">
                                {{ \App\Models\Booking::statusDisplayLabel($dv->status) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                Court
                            </dt>
                            <dd class="mt-1 font-medium text-stone-900 dark:text-stone-100">
                                {{ $dv->court?->name ?? '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                Time
                            </dt>
                            <dd class="mt-1 font-medium text-stone-900 dark:text-stone-100">
                                {{ $dv->starts_at->timezone($dvTz)->isoFormat('MMM D, h:mm A') }}
                                –
                                {{ $dv->ends_at->timezone($dvTz)->isoFormat('h:mm A') }}
                            </dd>
                        </div>
                        @if ($dv->user)
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                    Email
                                </dt>
                                <dd class="mt-1 break-all font-medium text-stone-900 dark:text-stone-100">
                                    {{ $dv->user->email }}
                                </dd>
                            </div>
                        @endif
                        @if ($dv->notes)
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                    Notes
                                </dt>
                                <dd class="mt-1 whitespace-pre-wrap text-stone-800 dark:text-stone-200">
                                    {{ $dv->notes }}
                                </dd>
                            </div>
                        @endif
                        @if ($dv->payment_method || $dv->payment_reference)
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                    Payment
                                </dt>
                                <dd class="mt-1 text-stone-800 dark:text-stone-200">
                                    @if ($dv->payment_method)
                                        {{ \App\Models\Booking::paymentMethodLabel($dv->payment_method) }}
                                    @endif
                                    @if ($dv->payment_reference)
                                        <span class="block font-mono text-xs">{{ $dv->payment_reference }}</span>
                                    @endif
                                </dd>
                            </div>
                        @endif
                        @if ($dv->deskSubmitter)
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                    Desk request by
                                </dt>
                                <dd class="mt-1 font-medium text-stone-900 dark:text-stone-100">
                                    {{ $dv->deskSubmitter->name }}
                                </dd>
                            </div>
                        @endif
                    </dl>
                    </div>
                </div>
            @else
                <div
                    class="w-full max-w-md overflow-hidden rounded-2xl border border-stone-200 bg-white p-6 shadow-2xl dark:border-stone-700 dark:bg-stone-900"
                    wire:click.stop
                >
                    <h3
                        id="desk-booking-detail-title"
                        class="font-display text-lg font-bold text-stone-900 dark:text-white"
                    >
                        Booking unavailable
                    </h3>
                    <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">
                        This reservation may have been removed or is outside your venue.
                    </p>
                    <button
                        type="button"
                        wire:click="closeDeskViewBooking"
                        class="mt-4 rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700"
                    >
                        Close
                    </button>
                </div>
            @endif
        </div>
    @endif
</div>
