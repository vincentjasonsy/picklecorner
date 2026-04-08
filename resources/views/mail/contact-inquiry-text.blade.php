{{ $inquiryLabel }}

Name: {{ $name }}
Email: {{ $email }}
@if (filled($phone))
Phone: {{ $phone }}
@endif
@if (filled($clubName))
Club / venue: {{ $clubName }}
@endif

Message:
{{ $messageBody }}
