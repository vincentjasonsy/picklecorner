@if (isset($title) && is_string($title) && $title !== '')
    <title>{{ config('app.name') }} - {{ $title }}</title>
@else
    <title>{{ config('app.name') }}</title>
@endif
