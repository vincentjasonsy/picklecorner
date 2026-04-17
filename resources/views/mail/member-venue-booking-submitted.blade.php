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
# Hi {{ $firstName }},

@if ($status === Booking::STATUS_PENDING_APPROVAL)
We received your booking request at **{{ $venueName }}** and it is **{{ $statusLabel }}** (the venue may need to approve it before it is final).
@elseif ($status === Booking::STATUS_CONFIRMED)
Your booking at **{{ $venueName }}** is recorded as **{{ $statusLabel }}** for the times below.
@elseif ($status === Booking::STATUS_DENIED)
Your request at **{{ $venueName }}** could not be confirmed — current status is **{{ $statusLabel }}**. If you have questions, contact the venue directly.
@else
We have an update for your booking at **{{ $venueName }}** (status: **{{ $statusLabel }}**).
@endif

@if ($bookingRequestId !== '')
<p class="text-sm text-gray-600">Request reference: <span class="font-mono">{{ $bookingRequestId }}</span></p>
@endif

**Your times**

| Court | When |
|:-----|:-----|
{!! $timesMarkdown !!}

@if ($showSummary)
**Summary**

Courts subtotal: {{ Money::formatMinor($courtSub, $currency) }}

@if ($coachFee > 0)
Coach (per your selection): {{ Money::formatMinor($coachFee, $currency) }}
@endif

@if ($bookingFee > 0)
Convenience fee{{ ($feeRuleLabel !== null && $feeRuleLabel !== '') ? ' ('.$feeRuleLabel.')' : '' }}: {{ Money::formatMinor($bookingFee, $currency) }}
@endif

@if ($gift !== null && (int) $gift > 0)
Checkout before gift {{ Money::formatMinor($beforeGift, $currency) }}, gift −{{ Money::formatMinor((int) $gift, $currency) }}, balance {{ Money::formatMinor($balance, $currency) }}
@else
Balance {{ Money::formatMinor($balance, $currency) }}
@endif

@endif

@if ($paymentLabel !== null && $paymentLabel !== '')
Payment: **{{ $paymentLabel }}**
@if ($paymentReference !== null && $paymentReference !== '')
(ref: {{ $paymentReference }})
@endif
@endif

@if ($status === Booking::STATUS_PENDING_APPROVAL)
We’ll email you again if the venue updates this request. You can also check status anytime in your account.
@else
You can review this booking anytime in your account.
@endif

<x-mail::button :url="$bookingsUrl">
    View my bookings
</x-mail::button>

<x-mail::subcopy>
Questions about this club? Reply to this email only if your venue invited you to — otherwise use the contact details on their listing.
</x-mail::subcopy>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
