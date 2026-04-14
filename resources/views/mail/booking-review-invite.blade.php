<x-mail::message>
# Hi {{ $firstName }},

Thanks for playing at **{{ $venueName }}**@if ($when !== '') on {{ $when }}@endif.

When you have a moment, rate the venue on **location**, **amenities**, and **price / value** (each out of five), plus an optional short comment. That breakdown helps other players compare clubs.

<x-mail::button :url="$venueReviewUrl" color="success">
    Rate this venue
</x-mail::button>

@if ($coachReviewUrl !== null && $coachName !== null)
You also had **{{ $coachName }}** on this booking — optional coach feedback (overall rating and comment):

<x-mail::button :url="$coachReviewUrl" color="primary">
    Rate your coach
</x-mail::button>
@endif

<x-mail::subcopy>
You’ll need to sign in if prompted. Links expire after a few weeks. You can also leave or update a review from [My corner]({{ route('account.dashboard', [], true) }}) or from the venue and booking pages while your review window is open.
</x-mail::subcopy>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
