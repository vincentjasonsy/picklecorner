@php
    use App\Models\Booking;
    use App\Support\Money;

    $reqSummary = $requestTotals ?? [];
    $courtSub = (int) ($reqSummary['court_subtotal_cents'] ?? 0);
    $coachFee = (int) ($reqSummary['coach_fee_total_cents'] ?? 0);
    $bookingFee = (int) ($reqSummary['booking_fee_total_cents'] ?? 0);
    $beforeGift = (int) ($reqSummary['checkout_total_before_gift_cents'] ?? 0);
    $gift = $reqSummary['gift_applied_total_cents'] ?? null;
    $balance = (int) ($reqSummary['balance_after_gift_cents'] ?? 0);
    $showSummary = $reqSummary !== [];
    $timesMarkdown = '';
    foreach ($lines as $line) {
        $timesMarkdown .= '| '.$line['court'].' | '.$line['when']." |\n";
    }
@endphp

<x-mail::message>
# New member booking — {{ $venueName }}

**Member:** {{ $bookerName }} ({{ $bookerEmail }})

**Status:** {{ $statusLabel }}

@if ($bookingRequestId !== '')
<p class="text-sm text-gray-600">Request reference: <span class="font-mono">{{ $bookingRequestId }}</span></p>
@endif

**Requested times**

| Court | When |
|:-----|:-----|
{!! $timesMarkdown !!}

@if ($showSummary)
**Totals (from checkout)**

Courts subtotal: {{ Money::formatMinor($courtSub, $currency) }}

@if ($coachFee > 0)
Coach fee: {{ Money::formatMinor($coachFee, $currency) }}
@endif

@if ($bookingFee > 0)
Convenience fee{{ ($feeRuleLabel !== null && $feeRuleLabel !== '') ? ' ('.$feeRuleLabel.')' : '' }}: {{ Money::formatMinor($bookingFee, $currency) }}
@endif

@if ($gift !== null && (int) $gift > 0)
Checkout {{ Money::formatMinor($beforeGift, $currency) }}, gift −{{ Money::formatMinor((int) $gift, $currency) }}, balance {{ Money::formatMinor($balance, $currency) }}
@else
Balance {{ Money::formatMinor($balance, $currency) }}
@endif

@endif

@if ($paymentLabel !== null && $paymentLabel !== '')
**Payment recorded:** {{ $paymentLabel }}
@if ($paymentReference !== null && $paymentReference !== '')
(ref: {{ $paymentReference }})
@endif
@endif

@if ($status === Booking::STATUS_PENDING_APPROVAL)
Please review pending requests when you have a moment.
@elseif ($status === Booking::STATUS_CONFIRMED)
@if (! empty($isOnlinePayment))
Paid online — booking lines are confirmed in the system.
@else
Confirmed per your venue desk rules — verify in your calendar if needed.
@endif
@endif

<x-mail::button :url="$venueBookingsUrl">
    Open venue bookings
</x-mail::button>

<x-mail::subcopy>
Reply-To is set to the member’s email for convenience.
</x-mail::subcopy>

{{ config('app.name') }}
</x-mail::message>
