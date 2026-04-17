<?php

use App\Livewire\Concerns\WithDashboardTable;
use App\Models\CourtClient;
use App\Models\User;
use App\Models\UserType;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::admin'), Title('Users')] class extends Component
{
    use WithDashboardTable;

    #[Url]
    public string $search = '';

    #[Url]
    public string $typeSlug = '';

    #[Url]
    public string $courtClientId = '';

    /** @return list<string> */
    protected function sortableColumns(): array
    {
        return ['name', 'email'];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeSlug(): void
    {
        $this->resetPage();
    }

    public function updatedCourtClientId(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function typeOptions(): Collection
    {
        return UserType::query()->orderBy('sort_order')->get();
    }

    #[Computed]
    public function courtClientFilterOptions(): Collection
    {
        return CourtClient::query()->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function usersPaginator()
    {
        $query = User::query()->with([
            'userType',
            'deskCourtClient',
            'administeredCourtClient',
        ]);

        if ($this->search !== '') {
            $s = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $this->search).'%';
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', $s)->orWhere('email', 'like', $s);
            });
        }

        if ($this->typeSlug !== '') {
            $query->whereHas('userType', fn ($q) => $q->where('slug', $this->typeSlug));
        }

        if ($this->courtClientId !== '') {
            $ccId = $this->courtClientId;
            $query->where(function ($q) use ($ccId) {
                $q->whereHas('administeredCourtClient', fn ($q2) => $q2->where('id', $ccId))
                    ->orWhereHas('deskCourtClient', fn ($q2) => $q2->where('id', $ccId))
                    ->orWhereHas('coachedCourts.court', fn ($q2) => $q2->where('court_client_id', $ccId))
                    ->orWhereHas('bookings', fn ($q2) => $q2->where('court_client_id', $ccId));
            });
        }

        if ($this->sortField !== '' && in_array($this->sortField, $this->sortableColumns(), true)) {
            $query->orderBy($this->sortField, $this->sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('name');
        }

        return $query->paginate($this->perPage);
    }

    public function deleteUser(string $userId): void
    {
        $user = User::query()->findOrFail($userId);

        if ($user->id === Auth::id()) {
            session()->flash('warning', 'You cannot delete your own account.');

            return;
        }

        if ($user->administeredCourtClient()->exists()) {
            session()->flash('warning', 'Reassign the venue admin before deleting this user.');

            return;
        }

        if ($user->isSuperAdmin()) {
            $superCount = User::query()
                ->whereHas('userType', fn ($q) => $q->where('slug', UserType::SLUG_SUPER_ADMIN))
                ->count();

            if ($superCount <= 1) {
                session()->flash('warning', 'Cannot delete the last super admin.');

                return;
            }
        }

        $email = $user->email;

        ActivityLogger::log(
            'user.deleted',
            ['email' => $email],
            null,
            "User {$email} deleted",
        );

        $user->delete();

        session()->flash('status', 'User deleted.');
    }
};
?>

@php
    $users = $this->usersPaginator;
    $headerSortField = $sortField !== '' ? $sortField : 'name';
    $headerSortDir = $sortField !== '' ? $sortDirection : 'asc';
@endphp

<x-dashboard.data-table :paginator="$users">
    <x-slot:toolbar>
        <x-dashboard.table-search
            wire:model.live.debounce.300ms="search"
            placeholder="Name or email"
        />
        <x-dashboard.table-filter wire:model.live="typeSlug" label="Role">
            <option value="">All roles</option>
            @foreach ($this->typeOptions as $type)
                <option value="{{ $type->slug }}">{{ $type->name }}</option>
            @endforeach
        </x-dashboard.table-filter>
        <x-dashboard.table-filter wire:model.live="courtClientId" label="Venue">
            <option value="">All venues</option>
            @foreach ($this->courtClientFilterOptions as $cc)
                <option value="{{ $cc->id }}">{{ $cc->name }}</option>
            @endforeach
        </x-dashboard.table-filter>
    </x-slot:toolbar>

    <x-slot:toolbarEnd>
        <x-dashboard.table-per-page />
        <a
            href="{{ route('admin.users.create') }}"
            wire:navigate
            class="font-display inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-xs font-bold uppercase tracking-wide text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600"
        >
            Add user
        </a>
    </x-slot:toolbarEnd>

    <x-slot:head>
        <tr>
            <x-dashboard.sortable-th
                column="name"
                label="Name"
                :active="$headerSortField"
                :direction="$headerSortDir"
            />
            <x-dashboard.sortable-th
                column="email"
                label="Email"
                :active="$headerSortField"
                :direction="$headerSortDir"
            />
            <th class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-300">Role</th>
            <th class="px-4 py-3 text-right font-semibold text-zinc-700 dark:text-zinc-300">Actions</th>
        </tr>
    </x-slot:head>

    @forelse ($users as $user)
        <tr wire:key="user-{{ $user->id }}">
            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">
                {{ $user->name }}
            </td>
            <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                {{ $user->email }}
            </td>
            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                <span>{{ $user->userType?->name ?? '—' }}</span>
            </td>
            <td class="px-4 py-3 text-right">
                @php
                    $superAdminCount = User::query()
                        ->whereHas('userType', fn ($q) => $q->where('slug', UserType::SLUG_SUPER_ADMIN))
                        ->count();
                    $canDelete =
                        $user->id !== auth()->id()
                        && ! $user->administeredCourtClient()->exists()
                        && (! $user->isSuperAdmin() || $superAdminCount > 1);
                @endphp
                <div class="flex flex-wrap items-center justify-end gap-x-3 gap-y-1">
                    <a
                        href="{{ route('admin.users.summary', $user) }}"
                        wire:navigate
                        class="text-xs font-semibold text-zinc-700 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white"
                    >
                        Summary
                    </a>
                    <a
                        href="{{ route('admin.users.edit', $user) }}"
                        wire:navigate
                        class="text-xs font-semibold text-zinc-700 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white"
                    >
                        Edit
                    </a>
                    @if ($canDelete)
                        <button
                            type="button"
                            wire:click="deleteUser('{{ $user->id }}')"
                            wire:confirm="Delete this user? This cannot be undone."
                            class="text-xs font-semibold text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                        >
                            Delete
                        </button>
                    @endif
                    @if (! $user->isSuperAdmin())
                        <form
                            method="POST"
                            action="{{ route('admin.users.impersonate', $user) }}"
                            class="inline"
                            onsubmit="return confirm('Sign in as {{ $user->name }}?');"
                        >
                            @csrf
                            <button
                                type="submit"
                                class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300"
                            >
                                Impersonate
                            </button>
                        </form>
                    @endif
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="4" class="px-4 py-8 text-center text-zinc-500">No users match.</td>
        </tr>
    @endforelse
</x-dashboard.data-table>
