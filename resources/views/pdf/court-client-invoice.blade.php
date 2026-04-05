@php
    use App\Models\Booking;
    use App\Models\CourtClientInvoice;
    use App\Support\Money;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $inv->reference }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #18181b;
            line-height: 1.4;
            margin: 0;
            padding: 28px 36px 36px;
        }
        h1 {
            font-size: 20px;
            margin: 0 0 4px;
            font-weight: bold;
        }
        .muted { color: #71717a; font-size: 10px; }
        .ref { font-family: DejaVu Sans Mono, monospace; font-size: 13px; margin: 0 0 16px; }
        .row { width: 100%; margin-bottom: 20px; }
        .row:after { content: ""; display: table; clear: both; }
        .col { float: left; width: 50%; }
        .col-right { text-align: right; }
        .label {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #71717a;
            margin: 0 0 4px;
        }
        .status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: bold;
        }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-unpaid { background: #fef3c7; color: #92400e; }
        .total-big { font-size: 22px; font-weight: bold; margin: 4px 0 0; }
        .box {
            border: 1px solid #e4e4e7;
            border-radius: 8px;
            padding: 16px;
            margin-top: 8px;
        }
        .notes {
            background: #fafafa;
            padding: 10px 12px;
            border-radius: 6px;
            margin: 12px 0 0;
            font-size: 10px;
        }
        .day-title {
            font-size: 12px;
            font-weight: bold;
            margin: 18px 0 8px;
            padding-bottom: 6px;
            border-bottom: 1px solid #e4e4e7;
        }
        .day-sub { float: right; font-weight: normal; color: #52525b; font-size: 11px; }
        table.lines { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.lines th {
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #71717a;
            padding: 6px 8px 6px 0;
            border-bottom: 1px solid #e4e4e7;
        }
        table.lines th.amt { text-align: right; }
        table.lines td {
            padding: 8px 8px 8px 0;
            border-bottom: 1px solid #f4f4f5;
            vertical-align: top;
        }
        table.lines td.amt { text-align: right; white-space: nowrap; font-weight: 600; }
        .footer-total {
            margin-top: 24px;
            padding-top: 14px;
            border-top: 1px solid #e4e4e7;
            text-align: right;
        }
        .footer-total .total-big { font-size: 18px; }
        .meta { margin-top: 20px; font-size: 9px; color: #a1a1aa; }
    </style>
</head>
<body>
    <p class="muted" style="margin:0 0 8px;">{{ config('app.name') }}</p>
    <h1>Invoice</h1>
    <p class="ref">{{ $inv->reference }}</p>

    <div class="row">
        <div class="col">
            <p class="label">Bill to</p>
            <p style="margin:0;font-weight:bold;">{{ $inv->courtClient?->name ?? '—' }}</p>
            @if ($inv->courtClient?->city)
                <p class="muted" style="margin:4px 0 0;">{{ $inv->courtClient->city }}</p>
            @endif
            <p class="muted" style="margin:8px 0 0;">
                Period {{ $inv->period_from?->toDateString() }} → {{ $inv->period_to?->toDateString() }}
            </p>
        </div>
        <div class="col col-right">
            @if ($inv->status === CourtClientInvoice::STATUS_PAID)
                <span class="status status-paid">Paid</span>
                @if ($inv->paid_at)
                    <p class="muted" style="margin:8px 0 0;">{{ $inv->paid_at->timezone($tz)->format('M j, Y g:i A') }}</p>
                @endif
            @else
                <span class="status status-unpaid">Unpaid</span>
            @endif
            <p class="label" style="margin-top:14px;">Total due</p>
            <p class="total-big">{{ Money::formatMinor($inv->total_cents, $inv->currency) }}</p>
            <p class="muted" style="margin:4px 0 0;">{{ $inv->bookings->count() }} booking(s)</p>
        </div>
    </div>

    <div class="box">
        @if ($inv->notes)
            <div class="notes">
                <strong>Notes:</strong> {{ $inv->notes }}
            </div>
        @endif

        @foreach ($byDay as $dayKey => $dayBookings)
            @php
                $dayLabel = \Carbon\Carbon::parse($dayKey, $tz)->format('l, M j, Y');
                $daySubtotal = (int) $dayBookings->sum(fn ($b) => (int) ($b->pivot->amount_cents ?? 0));
            @endphp
            <div class="day-title">
                {{ $dayLabel }}
                <span class="day-sub">{{ Money::formatMinor($daySubtotal, $inv->currency) }}</span>
            </div>
            <table class="lines">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Court</th>
                        <th>Guest</th>
                        <th>Status</th>
                        <th class="amt">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dayBookings as $b)
                        <tr>
                            <td>
                                {{ $b->starts_at?->timezone($tz)->format('g:i A') }}
                                – {{ $b->ends_at?->timezone($tz)->format('g:i A') }}
                            </td>
                            <td>{{ $b->court?->name ?? '—' }}</td>
                            <td>{{ $b->user?->name ?? '—' }}</td>
                            <td>{{ Booking::statusDisplayLabel($b->status) }}</td>
                            <td class="amt">{{ Money::formatMinor((int) ($b->pivot->amount_cents ?? 0), $inv->currency) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach

        <div class="footer-total">
            <p class="label">Invoice total</p>
            <p class="total-big">{{ Money::formatMinor($inv->total_cents, $inv->currency) }}</p>
        </div>
    </div>

    @if ($inv->creator)
        <p class="meta">
            Created by {{ $inv->creator->name }}
            · {{ $inv->created_at?->timezone($tz)->format('M j, Y g:i A') }}
        </p>
    @endif
</body>
</html>
