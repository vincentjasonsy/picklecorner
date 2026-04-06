<div class="space-y-8">
    <div class="flex flex-col gap-4 lg:flex-row lg:flex-wrap lg:items-end lg:justify-between">
        <div>
            <h1 class="font-display text-2xl font-bold text-zinc-900 dark:text-white">Customers</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $courtClient->name }}</span>
                · People who have booked here · search by name or email · add internal notes on each profile
            </p>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
        <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
            Search
        </label>
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="Name or email…"
            class="mt-1 w-full max-w-md rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
            autocomplete="off"
        />
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
            <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:bg-zinc-900/80 dark:text-zinc-400">
                <tr>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Bookings</th>
                    <th class="px-4 py-3">Last visit</th>
                    <th class="px-4 py-3">Notes</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($contacts as $row)
                    <tr class="hover:bg-zinc-50/80 dark:hover:bg-zinc-800/40">
                        <td class="px-4 py-3">
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $row->name }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $row->email }}</p>
                        </td>
                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                            {{ number_format((int) $row->venue_bookings_count) }}
                        </td>
                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                            @if ($row->last_booking_at)
                                {{ \Carbon\Carbon::parse($row->last_booking_at)->timezone(config('app.timezone'))->isoFormat('MMM D, YYYY') }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                            {{ number_format((int) $row->internal_notes_count) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a
                                href="{{ route('venue.crm.contacts.show', $row) }}"
                                wire:navigate
                                class="font-semibold text-emerald-700 hover:underline dark:text-emerald-400"
                            >
                                Open
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            @if (trim($search) !== '')
                                No customers match that search.
                            @else
                                No bookings yet — your customer list will fill as people reserve courts.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $contacts->links() }}
    </div>
</div>
