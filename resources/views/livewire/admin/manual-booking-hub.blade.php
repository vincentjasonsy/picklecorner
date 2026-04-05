@php
    $venues = $this->venuesPaginator;
    $headerSortField = $sortField !== '' ? $sortField : 'name';
    $headerSortDir = $sortField !== '' ? $sortDirection : 'asc';
@endphp

<x-dashboard.data-table :paginator="$venues">
    <x-slot:intro>
        <p class="text-sm text-zinc-600 dark:text-zinc-400">
            Pick a venue to open manual booking: choose courts and time slots on the grid, then record payment. Need a
            new venue?
            <a
                href="{{ route('admin.court-clients.index') }}"
                wire:navigate
                class="font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
            >
                Court clients
            </a>
        </p>
    </x-slot:intro>

    <x-slot:toolbar>
        <x-dashboard.table-search wire:model.live.debounce.300ms="q" placeholder="Venue or city" />
        <x-dashboard.table-filter wire:model.live="statusFilter" label="Status">
            <option value="">All statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </x-dashboard.table-filter>
    </x-slot:toolbar>

    <x-slot:toolbarEnd>
        <x-dashboard.table-per-page />
    </x-slot:toolbarEnd>

    <x-slot:head>
        <tr>
            <x-dashboard.sortable-th
                column="name"
                label="Venue"
                :active="$headerSortField"
                :direction="$headerSortDir"
            />
            <x-dashboard.sortable-th
                column="city"
                label="City"
                :active="$headerSortField"
                :direction="$headerSortDir"
            />
            <th class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-300">Admin</th>
            <x-dashboard.sortable-th
                column="hourly_rate_cents"
                label="Standard rate"
                :active="$headerSortField"
                :direction="$headerSortDir"
            />
            <x-dashboard.sortable-th
                column="is_active"
                label="Status"
                :active="$headerSortField"
                :direction="$headerSortDir"
            />
            <th class="px-4 py-3 text-right font-semibold text-zinc-700 dark:text-zinc-300"></th>
        </tr>
    </x-slot:head>

    @forelse ($venues as $client)
        <tr wire:key="mb-hub-{{ $client->id }}">
            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">
                {{ $client->name }}
            </td>
            <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                {{ $client->city ?? '—' }}
            </td>
            <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                {{ $client->admin?->email ?? '—' }}
            </td>
            <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                @if ($client->hourly_rate_cents)
                    {{ \App\Support\Money::formatMinor($client->hourly_rate_cents, $client->currency) }}/hr
                @else
                    —
                @endif
            </td>
            <td class="px-4 py-3">
                @if ($client->is_active)
                    <span
                        class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200"
                    >
                        Active
                    </span>
                @else
                    <span
                        class="rounded-full bg-zinc-200 px-2 py-0.5 text-xs font-medium text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200"
                    >
                        Inactive
                    </span>
                @endif
            </td>
            <td class="px-4 py-3 text-right">
                <a
                    href="{{ route('admin.court-clients.manual-booking', $client) }}"
                    wire:navigate
                    class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                >
                    Book
                </a>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="6" class="px-4 py-8 text-center text-zinc-500">No venues match.</td>
        </tr>
    @endforelse
</x-dashboard.data-table>
