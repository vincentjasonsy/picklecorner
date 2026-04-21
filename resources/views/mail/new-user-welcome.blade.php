<x-mail::message>
# Welcome to Pickle Corner, {{ $firstName }}!

Your account is ready. You can now browse courts and book your next game.

<x-mail::button :url="$dashboardUrl">
Go to My Account
</x-mail::button>

Need help? Just reply to this email.

</x-mail::message>
