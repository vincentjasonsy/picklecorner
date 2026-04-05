<?php

namespace App\Livewire\Admin;

use App\Models\CourtClient;
use App\Models\CourtClientInvoice;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin')]
#[Title('Client invoices')]
class InvoiceIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $clientFilter = '';

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingClientFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $query = CourtClientInvoice::query()
            ->with(['courtClient:id,name', 'creator:id,name']);

        if ($this->statusFilter === CourtClientInvoice::STATUS_PAID) {
            $query->where('status', CourtClientInvoice::STATUS_PAID);
        } elseif ($this->statusFilter === CourtClientInvoice::STATUS_UNPAID) {
            $query->where('status', CourtClientInvoice::STATUS_UNPAID);
        }

        if ($this->clientFilter !== '') {
            $query->where('court_client_id', $this->clientFilter);
        }

        $invoices = $query->orderByDesc('created_at')->paginate(20);

        $clients = CourtClient::query()->orderBy('name')->get(['id', 'name']);

        return view('livewire.admin.invoice-index', [
            'invoices' => $invoices,
            'clients' => $clients,
        ]);
    }
}
