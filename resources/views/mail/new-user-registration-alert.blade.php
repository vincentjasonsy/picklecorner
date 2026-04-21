<x-mail::message>
# New user registration

A new user just registered.

- **Name:** {{ $name }}
- **Email:** {{ $email }}
- **Registered at:** {{ $registeredAt ?? 'N/A' }}

<x-mail::button :url="route('admin.users.index', [], true)">
Open users
</x-mail::button>

</x-mail::message>
