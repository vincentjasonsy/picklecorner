<x-mail::message>
# Hi {{ $firstName }},

@if ($kind === 'upcoming')
A new **{{ $environmentLabel }}** court is on the way in your area: **{{ $courtName }}** at **{{ $venueName }}**@if ($city !== '') in {{ $city }}@endif.

**Opens:** {{ $opensLabel }} ({{ config('app.timezone') }}).

You can open the court page now to read more; booking may follow the venue’s schedule.
@else
A new **{{ $environmentLabel }}** court is available: **{{ $courtName }}** at **{{ $venueName }}**@if ($city !== '') in {{ $city }}@endif.

Browse the court, see photos, and book a time when you are ready.
@endif

<x-mail::button :url="$courtUrl">
    View this court
</x-mail::button>

<x-mail::button :url="$bookNowUrl" color="success">
    Explore Book now
</x-mail::button>

<x-mail::subcopy>
You’re receiving this because you opted in to product emails for your account and your home city matches this venue.
Manage preferences under {{ route('account.settings', [], true) }} (marketing emails).
</x-mail::subcopy>

See you on court,<br>
{{ config('app.name') }}
</x-mail::message>
