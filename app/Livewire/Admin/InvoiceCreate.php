<?php

namespace App\Livewire\Admin;

use App\Models\Booking;
use App\Models\CourtClient;
use App\Models\CourtClientInvoice;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('New invoice')]
class InvoiceCreate extends Component
{
    public string $courtClientId = '';

    public string $periodFrom = '';

    public string $periodTo = '';

    public string $notes = '';

    public function mount(): void
    {
        $tz = config('app.timezone', 'UTC');
        $this->periodFrom = Carbon::now($tz)->startOfMonth()->toDateString();
        $this->periodTo = Carbon::now($tz)->endOfMonth()->toDateString();
    }

    /**
     * @return Collection<int, Booking>
     */
    protected function eligibleBookingsQuery()
    {
        if ($this->courtClientId === '') {
            return collect();
        }

        $tz = config('app.timezone', 'UTC');
        try {
            $from = Carbon::parse($this->periodFrom, $tz)->startOfDay();
            $to = Carbon::parse($this->periodTo, $tz)->endOfDay();
        } catch (\Throwable) {
            return collect();
        }

        if ($from->gt($to)) {
            return collect();
        }

        return Booking::query()
            ->where('court_client_id', $this->courtClientId)
            ->whereBetween('starts_at', [$from, $to])
            ->countingTowardRevenue()
            ->eligibleForCourtClientInvoice()
            ->whereDoesntHave('courtClientInvoices')
            ->with(['court:id,name', 'user:id,name,email'])
            ->orderBy('starts_at')
            ->get();
    }

    public function previewBookings()
    {
        return $this->eligibleBookingsQuery();
    }

    public function previewTotalCents(): int
    {
        return (int) $this->eligibleBookingsQuery()->sum(fn (Booking $b) => (int) ($b->amount_cents ?? 0));
    }

    public function createInvoice(): void
    {
        $this->validate([
            'courtClientId' => ['required', 'uuid', 'exists:court_clients,id'],
            'periodFrom' => ['required', 'date'],
            'periodTo' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $tz = config('app.timezone', 'UTC');
        $from = Carbon::parse($this->periodFrom, $tz)->startOfDay();
        $to = Carbon::parse($this->periodTo, $tz)->endOfDay();

        if ($from->gt($to)) {
            $this->addError('periodTo', 'End date must be on or after the start date.');

            return;
        }

        if ($from->diffInDays($to) > 366) {
            $this->addError('periodTo', 'Period cannot exceed 366 days.');

            return;
        }

        $bookings = Booking::query()
            ->where('court_client_id', $this->courtClientId)
            ->whereBetween('starts_at', [$from, $to])
            ->countingTowardRevenue()
            ->eligibleForCourtClientInvoice()
            ->whereDoesntHave('courtClientInvoices')
            ->orderBy('starts_at')
            ->lockForUpdate()
            ->get();

        if ($bookings->isEmpty()) {
            $this->addError(
                'courtClientId',
                'No billable bookings in this range — only desk/admin manual bookings paid outside PayMongo (confirmed or completed, not already on an invoice).',
            );

            return;
        }

        $client = CourtClient::query()->findOrFail($this->courtClientId);

        $invoice = DB::transaction(function () use ($bookings, $client, $from, $to) {
            $reference = $this->uniqueReference();
            $total = (int) $bookings->sum(fn (Booking $b) => (int) ($b->amount_cents ?? 0));

            $invoice = CourtClientInvoice::query()->create([
                'court_client_id' => $client->id,
                'period_from' => $from->toDateString(),
                'period_to' => $to->toDateString(),
                'reference' => $reference,
                'status' => CourtClientInvoice::STATUS_UNPAID,
                'paid_at' => null,
                'total_cents' => $total,
                'currency' => $client->currency ?? 'PHP',
                'notes' => $this->notes !== '' ? $this->notes : null,
                'created_by' => auth()->id(),
            ]);

            foreach ($bookings as $b) {
                $invoice->bookings()->attach($b->id, [
                    'amount_cents' => (int) ($b->amount_cents ?? 0),
                ]);
            }

            return $invoice;
        });

        ActivityLogger::log(
            'invoice.created',
            [
                'reference' => $invoice->reference,
                'court_client_id' => $invoice->court_client_id,
                'booking_count' => $bookings->count(),
                'total_cents' => $invoice->total_cents,
            ],
            $invoice,
            "Invoice {$invoice->reference} created for {$client->name} ({$bookings->count()} bookings)",
        );

        session()->flash('status', "Invoice {$invoice->reference} created.");

        $this->redirect(route('admin.invoices.show', $invoice), navigate: true);
    }

    protected function uniqueReference(): string
    {
        do {
            $ref = CourtClientInvoice::generateReference();
        } while (CourtClientInvoice::query()->where('reference', $ref)->exists());

        return $ref;
    }

    public function render(): View
    {
        $clients = CourtClient::query()->orderBy('name')->get(['id', 'name', 'currency']);
        $preview = $this->eligibleBookingsQuery();
        $byDay = $preview
            ->groupBy(fn (Booking $b) => $b->starts_at?->timezone(config('app.timezone'))->format('Y-m-d') ?? '')
            ->sortKeys();
        $previewCurrency = CourtClient::query()->whereKey($this->courtClientId)->value('currency') ?? 'PHP';

        return view('livewire.admin.invoice-create', [
            'clients' => $clients,
            'previewBookings' => $preview,
            'previewByDay' => $byDay,
            'previewTotalCents' => $this->previewTotalCents(),
            'previewCurrency' => $previewCurrency,
        ]);
    }
}
