<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithDashboardTable;
use App\Models\ActivityLog;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Activity log')]
class ActivityIndex extends Component
{
    use WithDashboardTable;

    #[Url]
    public string $actionFilter = '';

    #[Url]
    public string $q = '';

    /** @return list<string> */
    protected function sortableColumns(): array
    {
        return ['created_at', 'action', 'description'];
    }

    public function updatingActionFilter(): void
    {
        $this->resetPage();
    }

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $query = ActivityLog::query()
            ->with(['user', 'courtClient']);

        if ($this->actionFilter !== '') {
            $query->where('action', $this->actionFilter);
        }

        if ($this->q !== '') {
            $term = '%'.addcslashes($this->q, '%_\\').'%';
            $query->where(function ($q) use ($term) {
                $q->where('action', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        if ($this->sortField !== '' && in_array($this->sortField, $this->sortableColumns(), true)) {
            $query->orderBy($this->sortField, $this->sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest('created_at');
        }

        $actionOptions = ActivityLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('livewire.admin.activity-index', [
            'logs' => $query->paginate($this->perPage),
            'actionOptions' => $actionOptions,
        ]);
    }
}
