<div class="w-full space-y-10">
    <div class="flex flex-wrap items-center justify-between gap-3">
        @if ($isVenuePortal)
            <a
                href="{{ route('venue.home') }}"
                wire:navigate
                class="text-sm font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
            >
                ← Venue portal
            </a>
            <a
                href="{{ route('venue.manual-booking') }}"
                wire:navigate
                class="text-sm font-semibold text-zinc-700 underline decoration-zinc-300 underline-offset-4 hover:text-emerald-600 dark:text-zinc-300 dark:hover:text-emerald-400"
            >
                Manual booking
            </a>
        @else
            <a
                href="{{ route('admin.court-clients.index') }}"
                wire:navigate
                class="text-sm font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
            >
                ← Back to court clients
            </a>
            <a
                href="{{ route('admin.court-clients.manual-booking', $courtClient) }}"
                wire:navigate
                class="text-sm font-semibold text-zinc-700 underline decoration-zinc-300 underline-offset-4 hover:text-emerald-600 dark:text-zinc-300 dark:hover:text-emerald-400"
            >
                Manual booking
            </a>
        @endif
    </div>

    @if ($isVenuePortal)
        <div
            class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900"
            role="status"
        >
            @if ($courtClient->hasPremiumSubscription())
                <p class="text-sm text-zinc-700 dark:text-zinc-300">
                    <span class="font-semibold text-zinc-900 dark:text-white">Premium plan</span>
                    — Gift cards and customer CRM are included.
                </p>
            @else
                <p class="text-sm text-zinc-700 dark:text-zinc-300">
                    <span class="font-semibold text-zinc-900 dark:text-white">Basic plan</span>
                    — Gift cards and CRM require Premium.
                    <a
                        href="{{ route('venue.plan') }}"
                        wire:navigate
                        class="font-semibold text-emerald-600 underline decoration-emerald-600/30 hover:text-emerald-700 dark:text-emerald-400"
                    >
                        View plans
                    </a>
                </p>
            @endif
        </div>
    @endif

    {{-- Venue, admin, pricing --}}
    <form wire:submit="saveVenue" class="space-y-6">
        <div class="grid gap-6 lg:grid-cols-2 lg:items-start">
            <div class="space-y-6">
                <div
                    class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Venue</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label
                                class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                            >
                                Name
                            </label>
                            <input
                                wire:model="name"
                                type="text"
                                class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                            />
                            @error('name')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label
                                class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                            >
                                URL slug
                            </label>
                            <input
                                wire:model="slug"
                                type="text"
                                class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                            />
                            @error('slug')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label
                                class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                            >
                                City
                            </label>
                            <input
                                wire:model="city"
                                type="text"
                                class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                            />
                        </div>
                        <div class="sm:col-span-2">
                            <label
                                class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                            >
                                Internal notes
                            </label>
                            <textarea
                                wire:model="notes"
                                rows="3"
                                class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                            ></textarea>
                        </div>
                        <div class="flex items-center gap-2 sm:col-span-2">
                            <input
                                wire:model="is_active"
                                id="is_active"
                                type="checkbox"
                                class="size-4 rounded border-zinc-300 dark:border-zinc-600"
                            />
                            <label for="is_active" class="text-sm text-zinc-700 dark:text-zinc-300">
                                Venue is active
                            </label>
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">
                        Desk manual booking requests
                    </h2>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        Controls what happens when desk staff submit a request from the desk portal.
                        <strong class="font-medium text-zinc-700 dark:text-zinc-300">Manual review</strong> sends each
                        one to the
                        @if ($isVenuePortal)
                            <a
                                href="{{ route('venue.bookings.pending') }}"
                                wire:navigate
                                class="font-semibold text-emerald-600 underline decoration-emerald-600/30 dark:text-emerald-400"
                            >
                                Manual booking requests
                            </a>
                        @else
                            <span class="font-semibold text-zinc-700 dark:text-zinc-300">Manual booking requests</span>
                            page (venue portal)
                        @endif
                        queue for approve or deny.
                        <strong class="font-medium text-zinc-700 dark:text-zinc-300">Auto-confirm</strong> or
                        <strong class="font-medium text-zinc-700 dark:text-zinc-300">auto-deny</strong> skips that queue.
                    </p>
                    <div class="mt-4">
                        <label
                            class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                            for="desk_booking_policy"
                        >
                            Auto approve / deny (or manual review)
                        </label>
                        <select
                            wire:model="desk_booking_policy"
                            id="desk_booking_policy"
                            class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                        >
                            <option value="{{ \App\Models\CourtClient::DESK_BOOKING_POLICY_MANUAL }}">
                                Manual review (admin approves or denies in venue portal)
                            </option>
                            <option value="{{ \App\Models\CourtClient::DESK_BOOKING_POLICY_AUTO_APPROVE }}">
                                Auto-confirm (bookings are confirmed immediately)
                            </option>
                            <option value="{{ \App\Models\CourtClient::DESK_BOOKING_POLICY_AUTO_DENY }}">
                                Auto-deny (requests are rejected; slots stay free)
                            </option>
                        </select>
                        @error('desk_booking_policy')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                @unless ($isVenuePortal)
                    <div
                        class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900"
                    >
                        <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Court admin</h2>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            One court admin per venue, and each court admin manages one venue only. Only unassigned
                            admins (or the current one) appear here.
                        </p>
                        <div class="mt-4">
                            <label
                                class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                            >
                                Assigned user
                            </label>
                            <select
                                wire:model="admin_user_id"
                                class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                            >
                                @foreach ($courtAdmins as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                                @endforeach
                            </select>
                            @error('admin_user_id')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div
                        class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900"
                    >
                        <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">
                            Subscription tier
                        </h2>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            Basic includes core operations. Premium unlocks gift cards and the customer CRM in the venue
                            portal.
                        </p>
                        <div class="mt-4">
                            <label
                                class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                                for="subscription_tier"
                            >
                                Tier
                            </label>
                            <select
                                wire:model="subscription_tier"
                                id="subscription_tier"
                                class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                            >
                                <option value="{{ \App\Models\CourtClient::TIER_BASIC }}">
                                    Basic — operations only (no gift cards or CRM)
                                </option>
                                <option value="{{ \App\Models\CourtClient::TIER_PREMIUM }}">
                                    Premium — gift cards &amp; customer CRM
                                </option>
                            </select>
                            @error('subscription_tier')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                @endunless
            </div>

            <div
                class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900"
            >
                <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Default pricing</h2>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    Venue defaults in Philippine pesos per hour (you can use decimals, e.g. 350.50). Courts can override
                    below.
                </p>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2 lg:col-span-1">
                        <label
                            class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                        >
                            Standard hourly (₱)
                        </label>
                        <input
                            wire:model="hourly_rate_pesos"
                            type="text"
                            inputmode="decimal"
                            placeholder="e.g. 350"
                            class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                        />
                        @error('hourly_rate_pesos')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-2 lg:col-span-1">
                        <label
                            class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                        >
                            Peak hourly (₱)
                        </label>
                        <input
                            wire:model="peak_hourly_rate_pesos"
                            type="text"
                            inputmode="decimal"
                            placeholder="e.g. 500"
                            class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                        />
                        @error('peak_hourly_rate_pesos')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-2 lg:max-w-xs">
                        <label
                            class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                        >
                            Currency (ISO)
                        </label>
                        <input
                            wire:model="currency"
                            type="text"
                            maxlength="3"
                            class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm uppercase dark:border-zinc-700 dark:bg-zinc-950"
                        />
                        @error('currency')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="border-t border-zinc-200 pt-6 dark:border-zinc-800">
            <button
                type="submit"
                class="font-display rounded-xl bg-emerald-600 px-6 py-3 text-sm font-bold uppercase tracking-wide text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600"
            >
                Save venue &amp; pricing
            </button>
        </div>
    </form>

    <div class="mt-10 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <livewire:venue.venue-client-gallery :court-client-id="$courtClient->id" :key="'venue-gallery-'.$courtClient->id" />
    </div>

    <div class="border-t border-zinc-200 pt-8 dark:border-zinc-800">
        @unless ($isVenuePortal)
            <h2 class="font-display text-xl font-bold text-zinc-900 dark:text-white">Court management</h2>
            <p class="mt-1 max-w-3xl text-sm text-zinc-600 dark:text-zinc-400">
                Courts are listed in order: all outdoor first, then all indoor. Names are automatic (Outdoor 1, Indoor 2, …).
                Default pricing comes from venue settings and weekly slot pricing below.
                Set the weekly venue schedule for when the facility accepts reservations (all courts follow this schedule).
            </p>
        @else
            <h2 class="font-display text-xl font-bold text-zinc-900 dark:text-white">Schedule &amp; slots</h2>
            <p class="mt-1 max-w-3xl text-sm text-zinc-600 dark:text-zinc-400">
                Weekly hours and slot tools. To add or remove courts, use
                <a
                    href="{{ route('venue.courts') }}"
                    wire:navigate
                    class="font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                >
                    Courts
                </a>
                — changes need super admin approval.
            </p>
        @endunless

        <div
            @class([
                'mt-6 grid gap-8 lg:items-start',
                'lg:grid-cols-2' => ! $isVenuePortal,
            ])
        >
            @unless ($isVenuePortal)
            <form wire:submit="saveCourts" class="space-y-4">
                <div
                    class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Courts</h3>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                Counting restarts per type (outdoor vs indoor). Changing type reorders the list.
                            </p>
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-2">
                            <button
                                type="button"
                                wire:click="addOutdoorCourt"
                                class="rounded-lg border border-zinc-200 px-3 py-1.5 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                            >
                                Add outdoor
                            </button>
                            <button
                                type="button"
                                wire:click="addIndoorCourt"
                                class="rounded-lg border border-zinc-200 px-3 py-1.5 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                            >
                                Add indoor
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead>
                                <tr
                                    class="border-b border-zinc-200 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-700 dark:text-zinc-400"
                                >
                                    <th class="pb-2 pr-3">Court</th>
                                    <th class="pb-2 pr-3">Type</th>
                                    <th class="pb-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @forelse ($courtRows as $index => $row)
                                    <tr wire:key="court-row-{{ $row['id'] ?? 'new-'.$index }}">
                                        <td class="py-2 pr-3 align-middle font-medium text-zinc-800 dark:text-zinc-200">
                                            {{ $this->courtLabel($index) }}
                                        </td>
                                        <td class="py-2 pr-3 align-top">
                                            <select
                                                wire:model.live="courtRows.{{ $index }}.environment"
                                                class="w-full min-w-[6rem] rounded-lg border border-zinc-200 px-2 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                            >
                                                <option value="outdoor">Outdoor</option>
                                                <option value="indoor">Indoor</option>
                                            </select>
                                        </td>
                                        <td class="py-2 align-top text-right">
                                            <button
                                                type="button"
                                                wire:click="removeCourt({{ $index }})"
                                                wire:confirm="Remove this court from the venue?"
                                                class="text-xs font-semibold text-red-600 hover:text-red-700 dark:text-red-400"
                                            >
                                                Remove
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="py-6 text-center text-sm text-zinc-500">
                                            No courts yet. Add at least one for per-court booking and pricing.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if (count($courtRows) > 0)
                        @foreach ($courtRows as $index => $_)
                            @error('courtRows.'.$index.'.environment')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        @endforeach
                    @endif

                    <div class="mt-6">
                        <button
                            type="submit"
                            class="font-display rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-bold uppercase tracking-wide text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600"
                        >
                            Save courts
                        </button>
                    </div>
                </div>
            </form>
            @endunless

            <form wire:submit="saveSchedule" class="space-y-4">
                <div
                    class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <h3 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Venue schedule</h3>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        Hours the facility is open for reservations (24h format). Mark closed on days with no play.
                    </p>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead>
                                <tr
                                    class="border-b border-zinc-200 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-700 dark:text-zinc-400"
                                >
                                    <th class="pb-2 pr-3">Day</th>
                                    <th class="pb-2 pr-3 text-center">Closed</th>
                                    <th class="pb-2 pr-3">Opens</th>
                                    <th class="pb-2">Closes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach ($scheduleRows as $i => $row)
                                    <tr wire:key="schedule-{{ $row['id'] }}">
                                        <td class="py-2 pr-3 font-medium text-zinc-800 dark:text-zinc-200">
                                            {{ $dayLabels[$row['day_of_week']] ?? 'Day' }}
                                        </td>
                                        <td class="py-2 pr-3 text-center">
                                            <input
                                                type="checkbox"
                                                wire:model.live="scheduleRows.{{ $i }}.is_closed"
                                                class="size-4 rounded border-zinc-300 dark:border-zinc-600"
                                            />
                                        </td>
                                        <td class="py-2 pr-3">
                                            <input
                                                type="time"
                                                wire:model="scheduleRows.{{ $i }}.opens_at"
                                                @disabled($scheduleRows[$i]['is_closed'] ?? false)
                                                class="w-full rounded-lg border border-zinc-200 px-2 py-1.5 text-sm disabled:opacity-50 dark:border-zinc-700 dark:bg-zinc-950"
                                            />
                                        </td>
                                        <td class="py-2">
                                            <input
                                                type="time"
                                                wire:model="scheduleRows.{{ $i }}.closes_at"
                                                @disabled($scheduleRows[$i]['is_closed'] ?? false)
                                                class="w-full rounded-lg border border-zinc-200 px-2 py-1.5 text-sm disabled:opacity-50 dark:border-zinc-700 dark:bg-zinc-950"
                                            />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @foreach ($scheduleRows as $i => $_)
                        @error('scheduleRows.'.$i.'.opens_at')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        @error('scheduleRows.'.$i.'.closes_at')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    @endforeach

                    <div class="mt-6">
                        <button
                            type="submit"
                            class="font-display rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-bold uppercase tracking-wide text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600"
                        >
                            Save schedule
                        </button>
                    </div>
                </div>
            </form>

            @if ($courtClient->courts->isNotEmpty())
                <div class="mt-8 space-y-4 lg:col-span-2">
                    <h3 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Court photos</h3>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        Shown on Book now cards and the public court page. Without photos, a default illustration is used.
                    </p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($courtClient->courts as $court)
                            <livewire:venue.court-gallery-editor :court-id="$court->id" :key="'settings-court-gal-'.$court->id" />
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        @php
            $slotGridHours = $this->slotHoursForGrid();
            $availabilityGridHours = $this->slotHoursForAvailabilityGrid();
            $availabilityDow = $this->availabilityDayOfWeek();
            $dateBlockLookup = $this->availabilityDateBlockLookup;
            $slotGridCourts = $this->courtsOrderedForGrid();
        @endphp

        <div class="mt-10 border-t border-zinc-200 pt-8 dark:border-zinc-800">
            <h3 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Weekly slots</h3>
            <p class="mt-1 max-w-3xl text-sm text-zinc-600 dark:text-zinc-400">
                Use the <strong>weekday chips</strong> for hourly <strong>pricing</strong> (same pattern as venue hours).
                Use the <strong>calendar date</strong> below for <strong>availability</strong> — blocks can apply to that
                date only, every matching weekday, or both. Use <strong>whole-venue closed days</strong> for holidays
                (no public bookings that calendar day). Confirmed bookings for players and coaches are on
                @if ($isVenuePortal)
                    <a
                        href="{{ route('venue.manual-booking') }}"
                        wire:navigate
                        class="font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                    >
                        Manual booking
                    </a>.
                @else
                    <a
                        href="{{ route('admin.court-clients.manual-booking', $courtClient) }}"
                        wire:navigate
                        class="font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                    >
                        Manual booking
                    </a>.
                @endif
            </p>

            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($dayLabels as $dow => $label)
                    <button
                        type="button"
                        wire:click="setSlotPricingDay({{ $dow }})"
                        class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors {{ $slotPricingDay === $dow ? 'bg-emerald-600 text-white' : 'border border-zinc-200 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div
                class="mt-8 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 px-4 py-3 dark:border-zinc-800 sm:px-5"
                >
                    <button
                        type="button"
                        wire:click="shiftClosureCalendarMonth(-1)"
                        class="rounded-lg border border-zinc-200 px-3 py-1.5 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                    >
                        Previous
                    </button>
                    <h4 class="text-center text-sm font-bold text-zinc-900 dark:text-white sm:text-base">
                        Whole-venue closed days — {{ $closureMonthLabel }}
                    </h4>
                    <button
                        type="button"
                        wire:click="shiftClosureCalendarMonth(1)"
                        class="rounded-lg border border-zinc-200 px-3 py-1.5 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                    >
                        Next
                    </button>
                </div>
                <p class="border-b border-zinc-200 px-4 py-2 text-xs text-zinc-600 dark:border-zinc-800 dark:text-zinc-400 sm:px-5">
                    Tap a day to mark the venue closed (holiday) or open again. The
                    <strong>availability</strong> date below jumps to that day so the grid stays in sync.
                </p>
                <div class="overflow-x-auto">
                    <div class="min-w-[520px]">
                        <div
                            class="grid grid-cols-7 border-b border-zinc-200 bg-zinc-50 text-center text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-950/80 dark:text-zinc-400"
                        >
                            <div class="px-1 py-2">Mon</div>
                            <div class="px-1 py-2">Tue</div>
                            <div class="px-1 py-2">Wed</div>
                            <div class="px-1 py-2">Thu</div>
                            <div class="px-1 py-2">Fri</div>
                            <div class="px-1 py-2">Sat</div>
                            <div class="px-1 py-2">Sun</div>
                        </div>
                        @foreach ($closureMonthWeeks as $week)
                            <div
                                class="grid grid-cols-7 divide-x divide-zinc-200 border-b border-zinc-200 last:border-b-0 dark:divide-zinc-800 dark:border-zinc-800"
                                wire:key="closure-w-{{ $week[0]['date']->format('Y-m-d') }}"
                            >
                                @foreach ($week as $day)
                                    @php
                                        $closureYmd = $day['date']->format('Y-m-d');
                                        $closureIsClosed = isset($closureMonthClosedLookup[$closureYmd]);
                                        $closureIsAvailDate = $closureYmd === $availabilityCalendarDate;
                                    @endphp
                                    <div class="p-1 sm:p-1.5" wire:key="closure-d-{{ $closureYmd }}">
                                        <button
                                            type="button"
                                            wire:click="toggleVenueClosedOnCalendarDay('{{ $closureYmd }}')"
                                            @class([
                                                'flex min-h-[3.25rem] w-full flex-col items-center justify-center rounded-lg border px-1 py-1.5 text-center text-xs font-semibold transition sm:min-h-[3.5rem]',
                                                'ring-2 ring-emerald-500 ring-offset-1 ring-offset-white dark:ring-emerald-400 dark:ring-offset-zinc-900' => $closureIsAvailDate,
                                                'border-zinc-200 bg-zinc-50/90 text-zinc-400 dark:border-zinc-700 dark:bg-zinc-950/50 dark:text-zinc-500' => ! $day['in_month'],
                                                'border-zinc-200 bg-white text-zinc-800 hover:border-emerald-400/60 hover:bg-emerald-50/70 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-emerald-950/25' => $day['in_month'] && ! $closureIsClosed,
                                                'border-red-300 bg-red-50 text-red-900 dark:border-red-900/60 dark:bg-red-950/35 dark:text-red-100' => $day['in_month'] && $closureIsClosed,
                                            ])
                                        >
                                            <span class="tabular-nums">{{ $day['date']->timezone($closureCalendarTz)->day }}</span>
                                            @if ($day['in_month'] && $closureIsClosed)
                                                <span class="mt-0.5 text-[10px] font-bold uppercase tracking-wide text-red-700 dark:text-red-300">
                                                    Closed
                                                </span>
                                            @elseif ($day['in_month'])
                                                <span class="mt-0.5 text-[10px] font-medium text-zinc-500 dark:text-zinc-400">Open</span>
                                            @endif
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
                <p class="flex flex-wrap items-center gap-x-4 gap-y-1 border-t border-zinc-200 px-4 py-2 text-[11px] text-zinc-500 dark:border-zinc-800 dark:text-zinc-400 sm:px-5">
                    <span class="inline-flex items-center gap-1.5">
                        <span
                            class="size-3 shrink-0 rounded border border-emerald-500 ring-2 ring-emerald-400/50 dark:ring-emerald-500/40"
                            aria-hidden="true"
                        ></span>
                        Emerald ring = availability date
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span
                            class="size-3 shrink-0 rounded border border-red-300 bg-red-50 dark:border-red-900/60 dark:bg-red-950/40"
                            aria-hidden="true"
                        ></span>
                        Red = venue closed (no bookings)
                    </span>
                </p>
            </div>

            @if ($slotGridCourts->isEmpty())
                <p
                    class="mt-6 rounded-lg border border-dashed border-zinc-300 px-4 py-6 text-center text-sm text-zinc-500 dark:border-zinc-600"
                >
                    Save at least one court before using weekly slot tools.
                </p>
            @else
                @if (count($slotGridHours) === 0)
                    <p
                        class="mt-6 rounded-lg border border-dashed border-zinc-300 px-4 py-6 text-center text-sm text-zinc-500 dark:border-zinc-600"
                    >
                        The selected weekday is closed or has no bookable hourly window. Adjust the venue schedule above
                        to edit pricing for that day.
                    </p>
                @else
                    <div class="mt-8">
                        <h4 class="font-display text-base font-bold text-zinc-900 dark:text-white">Hourly slot pricing</h4>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        Click a card for Normal, Peak, or Manual pesos per hour. Defaults follow venue or court overrides.
                    </p>
                    <div class="mt-3 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800">
                        <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse text-left text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/80">
                                    <th
                                        class="sticky left-0 z-10 bg-zinc-50 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:bg-zinc-800/80 dark:text-zinc-400"
                                    >
                                        Time
                                    </th>
                                    @foreach ($slotGridCourts as $court)
                                        <th
                                            class="min-w-[7.5rem] px-2 py-2 text-center text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-300"
                                        >
                                            {{ $court->name }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach ($slotGridHours as $hour)
                                    <tr wire:key="slot-price-row-{{ $hour }}">
                                        <td
                                            class="sticky left-0 z-10 whitespace-nowrap bg-white px-3 py-2 text-xs font-medium text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300"
                                        >
                                            {{ $this->slotHourLabel($hour) }}
                                        </td>
                                        @foreach ($slotGridCourts as $court)
                                            @php
                                                $cell = \App\Services\CourtSlotPricing::resolveForSlot(
                                                    $court,
                                                    $slotPricingDay,
                                                    $hour,
                                                );
                                                $cellStyle = match ($cell['mode']) {
                                                    \App\Models\CourtTimeSlotSetting::MODE_PEAK => 'border-amber-200 bg-amber-50/90 dark:border-amber-900/50 dark:bg-amber-950/25',
                                                    \App\Models\CourtTimeSlotSetting::MODE_MANUAL => 'border-violet-200 bg-violet-50/90 dark:border-violet-900/50 dark:bg-violet-950/25',
                                                    default => 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900/60',
                                                };
                                            @endphp
                                            <td class="p-1.5 align-middle">
                                                <button
                                                    type="button"
                                                    wire:click="openSlotEditor('{{ $court->id }}', {{ $hour }})"
                                                    class="flex w-full flex-col items-center justify-center rounded-lg border px-2 py-2 text-center text-xs transition-colors hover:border-emerald-500/60 hover:bg-emerald-50/80 dark:hover:bg-emerald-950/30 {{ $cellStyle }}"
                                                >
                                                    <span class="font-display text-[10px] font-bold text-zinc-400">
                                                        {{ $cell['short_label'] }}
                                                    </span>
                                                    <span class="mt-0.5 font-semibold text-zinc-800 dark:text-zinc-100">
                                                        {{ \App\Support\Money::formatMinor($cell['cents'], $currency) }}
                                                    </span>
                                                </button>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
                @endif

                <div class="mt-10">
                    <h4 class="font-display text-base font-bold text-zinc-900 dark:text-white">Availability</h4>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        Grid follows the <strong>calendar date</strong> (weekday
                        {{ $dayLabels[$availabilityDow] ?? '' }}). Matches the
                        <strong>whole-venue closed days</strong> calendar above. <strong>Blocked</strong> if closed for that
                        weekday every week and/or blocked for this date only.
                    </p>
                    <div
                        class="mt-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between sm:gap-4"
                    >
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                wire:click="shiftAvailabilityDate(-1)"
                                class="rounded-lg border border-zinc-200 px-3 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                            >
                                Previous day
                            </button>
                            <input
                                type="date"
                                wire:model.live="availabilityCalendarDate"
                                class="rounded-lg border border-zinc-200 px-3 py-2 text-sm text-zinc-800 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                            />
                            <button
                                type="button"
                                wire:click="shiftAvailabilityDate(1)"
                                class="rounded-lg border border-zinc-200 px-3 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                            >
                                Next day
                            </button>
                        </div>
                        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ $this->availabilityCalendarDateLabel() }}
                        </p>
                    </div>
                    @if (count($availabilityGridHours) === 0)
                        <p
                            class="mt-4 rounded-lg border border-dashed border-zinc-300 px-4 py-6 text-center text-sm text-zinc-500 dark:border-zinc-600"
                        >
                            @if ($this->isAvailabilityDateVenueClosure())
                                This calendar day is marked as a <strong class="font-semibold text-zinc-700 dark:text-zinc-300">whole-venue closed day</strong>
                                (holiday). There are no bookable hours — remove the closure in the calendar above or pick
                                another date. Weekly venue hours are unchanged.
                            @else
                                This calendar date is closed or has no bookable hourly window. Pick another date or adjust
                                venue hours.
                            @endif
                        </p>
                    @else
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
                                        @foreach ($slotGridCourts as $court)
                                            <th
                                                class="min-w-[7.5rem] px-2 py-2 text-center text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-300"
                                            >
                                                {{ $court->name }}
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                    @foreach ($availabilityGridHours as $hour)
                                        <tr wire:key="slot-avail-row-{{ $availabilityCalendarDate }}-{{ $hour }}">
                                            <td
                                                class="sticky left-0 z-10 whitespace-nowrap bg-white px-3 py-2 text-xs font-medium text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300"
                                            >
                                                {{ $this->slotHourLabel($hour) }}
                                            </td>
                                            @foreach ($slotGridCourts as $court)
                                                @php
                                                    $weeklyBlocked = $court->isWeeklySlotBlocked($availabilityDow, $hour);
                                                    $dateBlocked = isset($dateBlockLookup[$court->id.'-'.$hour]);
                                                    $blocked = $weeklyBlocked || $dateBlocked;
                                                    if ($blocked) {
                                                        $availStyle =
                                                            'border-red-200 bg-red-50/90 dark:border-red-900/50 dark:bg-red-950/25';
                                                    } else {
                                                        $availStyle =
                                                            'border-emerald-200 bg-emerald-50/50 dark:border-emerald-900/40 dark:bg-emerald-950/20';
                                                    }
                                                    $cellLabel = 'Open';
                                                    if ($weeklyBlocked && $dateBlocked) {
                                                        $cellLabel = 'Blocked';
                                                    } elseif ($dateBlocked) {
                                                        $cellLabel = 'Date block';
                                                    } elseif ($weeklyBlocked) {
                                                        $cellLabel = 'Weekly block';
                                                    }
                                                @endphp
                                                <td class="p-1.5 align-middle">
                                                    <button
                                                        type="button"
                                                        wire:click="openAvailabilityEditor('{{ $court->id }}', {{ $hour }})"
                                                        class="flex w-full flex-col items-center justify-center rounded-lg border px-2 py-2 text-center text-xs font-semibold transition-colors hover:border-emerald-500/60 hover:bg-emerald-50/80 dark:hover:bg-emerald-950/30 {{ $availStyle }}"
                                                    >
                                                        {{ $cellLabel }}
                                                    </button>
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    @if ($slotEditCourtId)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-900/60 p-4"
            wire:click="closeSlotEditor"
        >
            <div
                class="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-700 dark:bg-zinc-900"
                wire:click.stop
            >
                @php
                    $editCourt = \App\Models\Court::find($slotEditCourtId);
                @endphp
                <h4 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Slot pricing</h4>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ $editCourt?->name ?? 'Court' }}
                    ·
                    {{ $dayLabels[$slotPricingDay] ?? '' }}
                    ·
                    {{ $this->slotHourLabel($slotEditHour) }}
                </p>

                <form wire:submit="saveSlotPricing" class="mt-6 space-y-4">
                    <fieldset class="space-y-2">
                        <legend
                            class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                        >
                            Rate type
                        </legend>
                        <label
                            class="flex cursor-pointer items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-600"
                        >
                            <input type="radio" wire:model.live="slotEditMode" value="normal" class="size-4" />
                            <span class="text-sm text-zinc-800 dark:text-zinc-200">
                                Normal — venue or court standard rate
                            </span>
                        </label>
                        <label
                            class="flex cursor-pointer items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-600"
                        >
                            <input type="radio" wire:model.live="slotEditMode" value="peak" class="size-4" />
                            <span class="text-sm text-zinc-800 dark:text-zinc-200">
                                Peak — venue or court peak rate
                            </span>
                        </label>
                        <label
                            class="flex cursor-pointer items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-600"
                        >
                            <input type="radio" wire:model.live="slotEditMode" value="manual" class="size-4" />
                            <span class="text-sm text-zinc-800 dark:text-zinc-200">
                                Manual — fixed pesos per hour
                            </span>
                        </label>
                    </fieldset>

                    @if ($slotEditMode === 'manual')
                        <div>
                            <label
                                class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                            >
                                Pesos per hour (₱)
                            </label>
                            <input
                                type="text"
                                inputmode="decimal"
                                wire:model="slotEditManualPesos"
                                class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                placeholder="e.g. 400"
                            />
                            @error('slotEditManualPesos')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    @error('slotEditMode')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    @error('slotEditHour')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror

                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            wire:click="closeSlotEditor"
                            class="rounded-lg border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-700 dark:border-zinc-600 dark:text-zinc-200"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold uppercase tracking-wide text-white hover:bg-emerald-700 dark:bg-emerald-500"
                        >
                            Save slot
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($availEditCourtId)
        <div
            class="fixed inset-0 z-[60] flex items-center justify-center bg-zinc-900/60 p-4"
            wire:click="closeAvailabilityEditor"
        >
            <div
                class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-700 dark:bg-zinc-900"
                wire:click.stop
            >
                @php
                    $availCourt = \App\Models\Court::find($availEditCourtId);
                @endphp
                @php
                    $modalAvailDow = $this->availabilityDayOfWeek();
                @endphp
                <h4 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Availability</h4>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ $availCourt?->name ?? 'Court' }}
                    ·
                    {{ $this->availabilityCalendarDateLabel() }}
                    ({{ $dayLabels[$modalAvailDow] ?? '' }})
                    ·
                    {{ $this->slotHourLabel($availEditHour) }}
                </p>

                <form wire:submit="saveSlotAvailability" class="mt-6 space-y-4">
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-zinc-200 px-3 py-3 dark:border-zinc-600">
                        <input
                            type="checkbox"
                            wire:model="availEditBlockedOnDate"
                            class="mt-1 size-4 rounded border-zinc-300 dark:border-zinc-600"
                        />
                        <span class="text-sm text-zinc-800 dark:text-zinc-200">
                            <span class="font-semibold">Block this date only</span>
                            — hide this court at this hour on
                            {{ $this->availabilityCalendarDateLabel() }} from public booking.
                        </span>
                    </label>
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-zinc-200 px-3 py-3 dark:border-zinc-600">
                        <input
                            type="checkbox"
                            wire:model="availEditBlockedWeekly"
                            class="mt-1 size-4 rounded border-zinc-300 dark:border-zinc-600"
                        />
                        <span class="text-sm text-zinc-800 dark:text-zinc-200">
                            <span class="font-semibold">Block every {{ $dayLabels[$modalAvailDow] ?? 'weekday' }}</span>
                            — same hour on all future
                            {{ $dayLabels[$modalAvailDow] ?? 'weekdays' }} until you clear it.
                        </span>
                    </label>

                    @error('availEditHour')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror

                    <div class="flex justify-end gap-2">
                        <button
                            type="button"
                            wire:click="closeAvailabilityEditor"
                            class="rounded-lg border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-700 dark:border-zinc-600 dark:text-zinc-200"
                        >
                            Close
                        </button>
                        <button
                            type="submit"
                            class="rounded-lg bg-zinc-800 px-4 py-2 text-sm font-bold uppercase tracking-wide text-white hover:bg-zinc-900 dark:bg-zinc-200 dark:text-zinc-900 dark:hover:bg-white"
                        >
                            Save availability
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
