<?php

namespace App\Livewire\Concerns;

use Livewire\Attributes\Url;
use Livewire\WithPagination;

trait WithDashboardTable
{
    use WithPagination;

    /** @return list<string> */
    protected function sortableColumns(): array
    {
        return [];
    }

    #[Url]
    public string $sortField = '';

    #[Url]
    public string $sortDirection = 'desc';

    #[Url]
    public int $perPage = 25;

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        $allowed = $this->sortableColumns();
        if ($allowed === [] || ! in_array($field, $allowed, true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }
}
