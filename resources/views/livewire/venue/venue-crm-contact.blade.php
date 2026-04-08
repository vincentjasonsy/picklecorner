@php
    use App\Models\Booking;
    use App\Support\Money;
@endphp

<div class="space-y-8">
    <div>
        <a
            href="{{ route('venue.crm.index') }}"
            wire:navigate
            class="text-sm font-semibold text-emerald-700 hover:underline dark:text-emerald-400"
        >
            ← Customers
        </a>
        <div class="mt-3 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="font-display text-2xl font-bold text-zinc-900 dark:text-white">{{ $contact->name }}</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ $contact->email }}
                    ·
                    {{ $courtClient->name }}
                </p>
            </div>
            <a
                href="{{ route('venue.customers.summary', $contact) }}"
                wire:navigate
                class="shrink-0 rounded-lg border border-zinc-200 px-3 py-1.5 text-sm font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
            >
                Full summary
            </a>
        </div>
    </div>

    @if ($bookingStats)
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Bookings here</p>
                <p class="mt-2 font-display text-2xl font-bold text-zinc-900 dark:text-white">
                    {{ number_format((int) $bookingStats->c) }}
                </p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Lifetime value</p>
                <p class="mt-2 font-display text-2xl font-bold text-zinc-900 dark:text-white">
                    {{ Money::formatMinor((int) ($bookingStats->revenue_cents ?? 0), $courtClient->currency) }}
                </p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">First visit</p>
                <p class="mt-2 text-sm font-medium text-zinc-800 dark:text-zinc-200">
                    @if ($bookingStats->first_at)
                        {{ \Carbon\Carbon::parse($bookingStats->first_at)->timezone($tz)->isoFormat('MMM D, YYYY') }}
                    @else
                        —
                    @endif
                </p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Last visit</p>
                <p class="mt-2 text-sm font-medium text-zinc-800 dark:text-zinc-200">
                    @if ($bookingStats->last_at)
                        {{ \Carbon\Carbon::parse($bookingStats->last_at)->timezone($tz)->isoFormat('MMM D, YYYY') }}
                    @else
                        —
                    @endif
                </p>
            </div>
        </div>
    @endif

    <div class="grid gap-8 lg:grid-cols-2">
        <div class="space-y-4">
            <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Recent bookings</h2>
            <ul class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 bg-white dark:divide-zinc-800 dark:border-zinc-800 dark:bg-zinc-900">
                @forelse ($recentBookings as $b)
                    <li class="flex flex-col gap-1 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $b->starts_at?->timezone($tz)->isoFormat('MMM D, YYYY · h:mm A') }}
                            </p>
                            <p class="text-xs text-zinc-500">
                                {{ $b->court?->name ?? 'Court' }}
                                ·
                                {{ Booking::statusDisplayLabel($b->status) }}
                            </p>
                        </div>
                        <a
                            href="{{ route('venue.bookings.show', $b) }}"
                            wire:navigate
                            class="shrink-0 text-sm font-semibold text-emerald-700 hover:underline dark:text-emerald-400"
                        >
                            View
                        </a>
                    </li>
                @empty
                    <li class="px-4 py-6 text-sm text-zinc-500 dark:text-zinc-400">No bookings found.</li>
                @endforelse
            </ul>
        </div>

        <div class="space-y-4">
            <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Internal notes</h2>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                Visible to venue admins only — not shown to the customer.
            </p>

            <form wire:submit="addNote" class="space-y-2">
                <textarea
                    wire:model="newNoteBody"
                    rows="4"
                    placeholder="Follow-up reminders, preferences, membership context…"
                    class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                ></textarea>
                @error('newNoteBody')
                    <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                <button
                    type="submit"
                    class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600"
                >
                    Save note
                </button>
            </form>

            <ul class="space-y-3">
                @forelse ($notes as $note)
                    <li class="rounded-xl border border-zinc-200 bg-zinc-50/80 p-4 dark:border-zinc-800 dark:bg-zinc-900/60">
                        <p class="whitespace-pre-wrap text-sm text-zinc-800 dark:text-zinc-200">{{ $note->body }}</p>
                        <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $note->created_at?->timezone($tz)->isoFormat('MMM D, YYYY · h:mm A') }}
                            @if ($note->createdBy)
                                · {{ $note->createdBy->name }}
                            @endif
                        </p>
                    </li>
                @empty
                    <li class="text-sm text-zinc-500 dark:text-zinc-400">No notes yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
