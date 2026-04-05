<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function adminBookings(Request $request): StreamedResponse|Response
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        [$from, $to] = $this->validatedRange($request);

        $filename = 'bookings-'.$from->toDateString().'-to-'.$to->toDateString().'.csv';

        return response()->streamDownload(function () use ($from, $to): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'booking_id',
                'venue',
                'court',
                'guest_name',
                'guest_email',
                'starts_at',
                'ends_at',
                'status',
                'amount_cents',
                'currency',
                'payment_method',
                'gift_card_code',
                'gift_redemption_cents',
                'desk_submitted_by',
                'booking_created_at',
            ]);

            Booking::query()
                ->whereBetween('starts_at', [$from, $to])
                ->with([
                    'courtClient:id,name',
                    'court:id,name',
                    'user:id,name,email',
                    'deskSubmitter:id,name',
                    'giftCard:id,code',
                ])
                ->orderBy('starts_at')
                ->chunk(400, function ($chunk) use ($out): void {
                    foreach ($chunk as $b) {
                        /** @var Booking $b */
                        fputcsv($out, [
                            $b->id,
                            $b->courtClient?->name ?? '',
                            $b->court?->name ?? '',
                            $b->user?->name ?? '',
                            $b->user?->email ?? '',
                            $b->starts_at?->toIso8601String() ?? '',
                            $b->ends_at?->toIso8601String() ?? '',
                            $b->status,
                            $b->amount_cents ?? '',
                            $b->currency ?? '',
                            $b->payment_method ?? '',
                            $b->giftCard?->code ?? '',
                            $b->gift_card_redeemed_cents ?? '',
                            $b->deskSubmitter?->name ?? '',
                            $b->created_at?->toIso8601String() ?? '',
                        ]);
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function venueBookings(Request $request): StreamedResponse|Response
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isCourtAdmin(), 403);

        $client = $user->administeredCourtClient;
        abort_unless($client !== null, 403);

        [$from, $to] = $this->validatedRange($request);

        $filename = 'bookings-'.preg_replace('/[^a-z0-9_-]+/i', '-', $client->slug).'-'.$from->toDateString().'-to-'.$to->toDateString().'.csv';

        return response()->streamDownload(function () use ($from, $to, $client): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'booking_id',
                'court',
                'guest_name',
                'guest_email',
                'starts_at',
                'ends_at',
                'status',
                'amount_cents',
                'currency',
                'payment_method',
                'gift_card_code',
                'gift_redemption_cents',
                'desk_submitted_by',
                'booking_created_at',
            ]);

            Booking::query()
                ->where('court_client_id', $client->id)
                ->whereBetween('starts_at', [$from, $to])
                ->with([
                    'court:id,name',
                    'user:id,name,email',
                    'deskSubmitter:id,name',
                    'giftCard:id,code',
                ])
                ->orderBy('starts_at')
                ->chunk(400, function ($chunk) use ($out): void {
                    foreach ($chunk as $b) {
                        /** @var Booking $b */
                        fputcsv($out, [
                            $b->id,
                            $b->court?->name ?? '',
                            $b->user?->name ?? '',
                            $b->user?->email ?? '',
                            $b->starts_at?->toIso8601String() ?? '',
                            $b->ends_at?->toIso8601String() ?? '',
                            $b->status,
                            $b->amount_cents ?? '',
                            $b->currency ?? '',
                            $b->payment_method ?? '',
                            $b->giftCard?->code ?? '',
                            $b->gift_card_redeemed_cents ?? '',
                            $b->deskSubmitter?->name ?? '',
                            $b->created_at?->toIso8601String() ?? '',
                        ]);
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function validatedRange(Request $request): array
    {
        $tz = config('app.timezone', 'UTC');
        $defaultFrom = Carbon::now($tz)->subDays(90)->startOfDay();
        $defaultTo = Carbon::now($tz)->endOfDay();

        $from = $request->filled('from')
            ? Carbon::parse($request->string('from'), $tz)->startOfDay()
            : $defaultFrom;
        $to = $request->filled('to')
            ? Carbon::parse($request->string('to'), $tz)->endOfDay()
            : $defaultTo;

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        if ($from->diffInDays($to) > 800) {
            abort(422, 'Date range too large (max 800 days).');
        }

        return [$from, $to];
    }
}
