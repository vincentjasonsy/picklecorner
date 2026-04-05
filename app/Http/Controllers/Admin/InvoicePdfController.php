<?php

namespace App\Http\Controllers\Admin;

use App\Models\CourtClientInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class InvoicePdfController
{
    public function __invoke(CourtClientInvoice $invoice): Response
    {
        $invoice->load(['courtClient', 'creator', 'bookings.court', 'bookings.user']);

        $tz = config('app.timezone', 'UTC');
        $byDay = $invoice->bookings
            ->groupBy(fn ($b) => $b->starts_at?->timezone($tz)->format('Y-m-d') ?? '')
            ->sortKeys();

        $filename = str_replace(['/', '\\'], '-', $invoice->reference).'.pdf';

        return Pdf::loadView('pdf.court-client-invoice', [
            'inv' => $invoice,
            'byDay' => $byDay,
            'tz' => $tz,
        ])
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }
}
