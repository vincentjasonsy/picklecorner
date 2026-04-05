@php
    $headerSortField = $sortField !== '' ? $sortField : 'created_at';
    $headerSortDir = $sortField !== '' ? $sortDirection : 'desc';
@endphp

<x-dashboard.data-table :paginator="$logs">
    <x-slot:intro>
        <p class="text-sm text-zinc-600 dark:text-zinc-400">
            Sitewide audit trail for security, support, and reporting. Listen for the
            <code class="rounded bg-zinc-200 px-1 py-0.5 text-xs dark:bg-zinc-800">ActivityLogged</code>
            event to drive in-app or email notifications.
        </p>
    </x-slot:intro>

    <x-slot:toolbar>
        <x-dashboard.table-search
            wire:model.live.debounce.400ms="q"
            placeholder="Action or description"
        />
        <x-dashboard.table-filter wire:model.live="actionFilter" label="Action">
            <option value="">All actions</option>
            @foreach ($actionOptions as $action)
                <option value="{{ $action }}">{{ $action }}</option>
            @endforeach
        </x-dashboard.table-filter>
    </x-slot:toolbar>

    <x-slot:toolbarEnd>
        <x-dashboard.table-per-page />
    </x-slot:toolbarEnd>

    <x-slot:head>
        <tr>
            <x-dashboard.sortable-th
                column="created_at"
                label="When"
                :active="$headerSortField"
                :direction="$headerSortDir"
            />
            <x-dashboard.sortable-th
                column="action"
                label="Action"
                :active="$headerSortField"
                :direction="$headerSortDir"
            />
            <th class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-300">Actor</th>
            <th class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-300">Venue</th>
            <x-dashboard.sortable-th
                column="description"
                label="Summary"
                :active="$headerSortField"
                :direction="$headerSortDir"
            />
        </tr>
    </x-slot:head>

    @forelse ($logs as $log)
        <tr wire:key="log-{{ $log->id }}">
            <td class="whitespace-nowrap px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400">
                <span title="{{ $log->created_at?->toIso8601String() }}">
                    {{ $log->created_at?->timezone(config('app.timezone'))->format('M j, H:i') }}
                </span>
            </td>
            <td class="px-4 py-3">
                <span
                    class="rounded-md bg-zinc-100 px-2 py-0.5 font-mono text-xs text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200"
                >
                    {{ $log->action }}
                </span>
            </td>
            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                {{ $log->user?->email ?? '—' }}
            </td>
            <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-400">
                {{ $log->courtClient?->name ?? '—' }}
            </td>
            <td class="max-w-md px-4 py-3 text-zinc-600 dark:text-zinc-400">
                {{ $log->description ?? '—' }}
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="5" class="px-4 py-10 text-center text-zinc-500">No activity yet.</td>
        </tr>
    @endforelse
</x-dashboard.data-table>
