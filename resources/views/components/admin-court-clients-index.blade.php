<?php

use App\Livewire\Concerns\WithDashboardTable;
use App\Models\CourtClient;
use App\Services\ActivityLogger;
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
        return ['name', 'city', 'hourly_rate_cents', 'venue_status'];
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

        if ($this->statusFilter === \App\Models\CourtClient::VENUE_STATUS_ACTIVE) {
            $query->where('venue_status', \App\Models\CourtClient::VENUE_STATUS_ACTIVE);
        } elseif ($this->statusFilter === \App\Models\CourtClient::VENUE_STATUS_UPCOMING) {
            $query->where('venue_status', \App\Models\CourtClient::VENUE_STATUS_UPCOMING);
        } elseif ($this->statusFilter === \App\Models\CourtClient::VENUE_STATUS_INACTIVE) {
            $query->where('venue_status', \App\Models\CourtClient::VENUE_STATUS_INACTIVE);
        }

        if ($this->sortField !== '' && in_array($this->sortField, $this->sortableColumns(), true)) {
            $query->orderBy($this->sortField, $this->sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('name');
        }

        return $query->paginate($this->perPage);
    }

    public function deleteCourtClient(string $id): void
    {
        $client = CourtClient::query()->find($id);
        if ($client === null) {
            return;
        }

        $blockers = [];
        if ($client->bookings()->exists()) {
            $blockers[] = 'bookings';
        }
        if ($client->invoices()->exists()) {
            $blockers[] = 'invoices';
        }
        if ($client->giftCards()->exists()) {
            $blockers[] = 'gift cards';
        }

        if ($blockers !== []) {
            session()->flash(
                'warning',
                'Cannot delete this venue while it has '.implode(', ', $blockers).'.',
            );

            return;
        }

        $name = $client->name;

        ActivityLogger::log(
            'court_client.deleted',
            ['name' => $name, 'slug' => $client->slug],
            $client,
            'Court client “'.$name.'” deleted',
        );

        $client->delete();

        session()->flash('status', 'Venue deleted.');
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
            <option value="{{ \App\Models\CourtClient::VENUE_STATUS_ACTIVE }}">Active</option>
            <option value="{{ \App\Models\CourtClient::VENUE_STATUS_UPCOMING }}">Upcoming</option>
            <option value="{{ \App\Models\CourtClient::VENUE_STATUS_INACTIVE }}">Inactive</option>
        </x-dashboard.table-filter>
    </x-slot:toolbar>

    <x-slot:toolbarEnd>
        <x-dashboard.table-per-page />
        <a
            href="{{ route('admin.court-clients.create') }}"
            wire:navigate
            class="font-display inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-xs font-bold uppercase tracking-wide text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600"
        >
            New venue
        </a>
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
                column="venue_status"
                label="Status"
                :active="$headerSortField"
                :direction="$headerSortDir"
            />
            <th class="px-4 py-3 text-right font-semibold text-zinc-700 dark:text-zinc-300">Actions</th>
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
                @if ($client->venue_status === \App\Models\CourtClient::VENUE_STATUS_ACTIVE)
                    <span
                        class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200"
                    >
                        Active
                    </span>
                @elseif ($client->venue_status === \App\Models\CourtClient::VENUE_STATUS_UPCOMING)
                    <span
                        class="rounded-full bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-900 dark:bg-sky-950/50 dark:text-sky-200"
                    >
                        Upcoming
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
                <div class="flex flex-wrap items-center justify-end gap-x-3 gap-y-1">
                    <a
                        href="{{ route('admin.court-clients.edit', $client) }}"
                        wire:navigate
                        class="text-xs font-semibold text-zinc-700 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white"
                    >
                        Edit
                    </a>
                    <button
                        type="button"
                        wire:click="deleteCourtClient('{{ $client->id }}')"
                        wire:confirm="Delete this venue? Courts and schedule rows will be removed. This cannot be undone."
                        class="text-xs font-semibold text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                    >
                        Delete
                    </button>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="6" class="px-4 py-8 text-center text-zinc-500">No venues match.</td>
        </tr>
    @endforelse
</x-dashboard.data-table>
