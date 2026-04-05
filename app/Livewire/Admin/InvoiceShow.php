<?php

namespace App\Livewire\Admin;

use App\Models\CourtClientInvoice;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Invoice')]
class InvoiceShow extends Component
{
    public CourtClientInvoice $invoice;

    public function mount(CourtClientInvoice $invoice): void
    {
        $this->invoice = $invoice->load(['courtClient', 'creator', 'bookings.court', 'bookings.user']);
    }

    public function markPaid(): void
    {
        if ($this->invoice->status === CourtClientInvoice::STATUS_PAID) {
            return;
        }

        $this->invoice->update([
            'status' => CourtClientInvoice::STATUS_PAID,
            'paid_at' => now(),
        ]);
        $this->invoice->refresh();

        ActivityLogger::log(
            'invoice.marked_paid',
            ['reference' => $this->invoice->reference],
            $this->invoice,
            "Invoice {$this->invoice->reference} marked paid",
        );

        session()->flash('status', 'Invoice marked as paid.');
    }

    public function markUnpaid(): void
    {
        if ($this->invoice->status === CourtClientInvoice::STATUS_UNPAID) {
            return;
        }

        $this->invoice->update([
            'status' => CourtClientInvoice::STATUS_UNPAID,
            'paid_at' => null,
        ]);
        $this->invoice->refresh();

        ActivityLogger::log(
            'invoice.marked_unpaid',
            ['reference' => $this->invoice->reference],
            $this->invoice,
            "Invoice {$this->invoice->reference} marked unpaid",
        );

        session()->flash('status', 'Invoice marked as unpaid.');
    }

    public function render(): View
    {
        $tz = config('app.timezone', 'UTC');
        $bookings = $this->invoice->bookings;
        $byDay = $bookings
            ->groupBy(fn ($b) => $b->starts_at?->timezone($tz)->format('Y-m-d') ?? '')
            ->sortKeys();

        return view('livewire.admin.invoice-show', [
            'byDay' => $byDay,
            'tz' => $tz,
        ]);
    }
}
