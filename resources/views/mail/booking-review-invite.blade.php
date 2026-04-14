<x-mail::message>
# Hi {{ $firstName }},

Thanks for playing at **{{ $venueName }}**@if ($when !== '') on {{ $when }}@endif.

When you have a moment, leave a quick rating — it helps other players and the venue.

<x-mail::button :url="$venueReviewUrl" color="success">
    Rate this venue
</x-mail::button>

@if ($coachReviewUrl !== null && $coachName !== null)
You also had **{{ $coachName }}** on this booking — optional coach feedback:

<x-mail::button :url="$coachReviewUrl" color="primary">
    Rate your coach
</x-mail::button>
@endif

<x-mail::subcopy>
You’ll need to sign in if prompted. Links expire after a few weeks; you can still leave a review from the venue or booking pages while your review window is open.
</x-mail::subcopy>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
