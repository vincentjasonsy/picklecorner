<x-mail::message>
# Hey {{ $firstName }}, ready for another game?

It has been **{{ $days }} days** since your last booking with us. The courts are open — lock a slot while the calendar still has your favorite times.

<x-mail::panel>
**Quick tip:** book from your account for a smooth checkout, or browse every live venue in one place. Venues you have played before are listed first so you can get back on familiar ground fast.
</x-mail::panel>

<x-mail::button :url="$bookUrl" color="success">
    Book from my account
</x-mail::button>

<x-mail::button :url="$browseUrl" color="primary">
    Browse all courts
</x-mail::button>

## Courts open for booking

@if (count($courts) === 0)
We could not find bookable courts right now. Check back soon, or contact support if this keeps happening.
@else
**Picked for you:** places you have played before first, then more great options nearby.

@foreach ($courts as $court)
<x-mail::panel>
**{{ $court['badge'] }}** · {{ $court['environment_label'] }}

### {{ $court['court_name'] }}
{{ $court['venue_name'] }}@if (! empty($court['city'])) — {{ $court['city'] }}@endif

<x-mail::button :url="$court['book_url']" color="secondary">
    Book this court
</x-mail::button>
<x-mail::button :url="$court['venue_book_url']" color="secondary">
    Venue page
</x-mail::button>
</x-mail::panel>
@endforeach
@endif

## Ideas for your next session

@foreach ($tips as $tip)
- {{ $tip }}
@endforeach

See you on the court.

— {{ config('app.name') }}

<x-mail::subcopy>
If you already booked and still got this email, our reminder may have crossed paths with your reservation — you can ignore this one.
</x-mail::subcopy>

<x-mail::button :url="$unsubscribeUrl" color="error">
    Unsubscribe from booking reminders
</x-mail::button>

<x-mail::subcopy>
This link is tied to your account. If the button does not work, copy the URL into your browser.
</x-mail::subcopy>
</x-mail::message>
