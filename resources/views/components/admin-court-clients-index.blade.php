<?php

use App\Livewire\Concerns\WithDashboardTable;
use App\Models\CourtClient;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::admin'), Title('Court clients')] class extends Component
{
    use WithDashboardTable;

    #[Url]
    public string $q = '';

    #[Url]
    public string $statusFilter = '';

    /** @return list<string> */
    protected function sortableColumns(): array
    {
        return ['name', 'city', 'hourly_rate_cents', 'is_active'];
    }

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function clientsPaginator()
    {
        $query = CourtClient::query()->with('admin');

        if ($this->q !== '') {
            $s = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $this->q).'%';
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', $s)->orWhere('city', 'like', $s);
            });
        }

        if ($this->statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($this->statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        if ($this->sortField !== '' && in_array($this->sortField, $this->sortableColumns(), true)) {
            $query->orderBy($this->sortField, $this->sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('name');
        }

        return $query->paginate($this->perPage);
    }
};
?>

@php
    $clients = $this->clientsPaginator;
    $headerSortField = $sortField !== '' ? $sortField : 'name';
    $headerSortDir = $sortField !== '' ? $sortDirection : 'asc';
@endphp

<x-dashboard.data-table :paginator="$clients">
    <x-slot:intro>
        <p class="text-sm text-zinc-600 dark:text-zinc-400">
            Each venue has one court admin, pricing fields, and can be deactivated without deleting history.
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

    @forelse ($clients as $client)
        <tr wire:key="cc-{{ $client->id }}">
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
                    href="{{ route('admin.court-clients.edit', $client) }}"
                    wire:navigate
                    class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                >
                    Edit
                </a>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="6" class="px-4 py-8 text-center text-zinc-500">No venues match.</td>
        </tr>
    @endforelse
</x-dashboard.data-table>
