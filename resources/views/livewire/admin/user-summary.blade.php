@php
    use App\Models\Booking;
    use App\Support\Money;

    $tz = config('app.timezone');
    $venueScoped = $venueScoped ?? false;
@endphp

<div class="mx-auto max-w-5xl space-y-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a
            href="{{ $venueScoped ? $venueBackUrl : route('admin.users.index') }}"
            wire:navigate
            class="text-sm font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
        >
            @if ($venueScoped)
                ← Customers
            @else
                ← Back to users
            @endif
        </a>
        @unless ($venueScoped)
            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('admin.users.edit', $user) }}"
                    wire:navigate
                    class="rounded-lg border border-zinc-200 px-3 py-1.5 text-sm font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                >
                    Edit user
                </a>
            </div>
        @endunless
    </div>

    <div>
        <h1 class="font-display text-2xl font-bold text-zinc-900 dark:text-white">{{ $user->name }}</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $user->email }}</p>
        @unless ($venueScoped)
            <p class="mt-0.5 font-mono text-xs text-zinc-500 dark:text-zinc-500">{{ $user->id }}</p>
        @endunless
        @if ($venueScoped && $venueCourtClient)
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $venueCourtClient->name }}</span>
                · Bookings, open play, and activity below are limited to this venue.
            </p>
        @endif
    </div>

    <dl
        class="grid gap-4 rounded-xl border border-zinc-200 bg-white p-6 text-sm dark:border-zinc-800 dark:bg-zinc-900 sm:grid-cols-2 lg:grid-cols-3"
    >
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Role</dt>
            <dd class="mt-1 text-zinc-900 dark:text-zinc-100">{{ $user->userType?->name ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                Member since
            </dt>
            <dd class="mt-1 text-zinc-900 dark:text-zinc-100">
                {{ $user->created_at?->timezone($tz)->isoFormat('MMM D, YYYY') ?? '—' }}
            </dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                Demo account
            </dt>
            <dd class="mt-1 text-zinc-900 dark:text-zinc-100">
                @if ($user->isDemoAccount())
                    Yes
                    @if ($user->demo_expires_at)
                        <span class="block text-xs text-zinc-500">
                            Expires {{ $user->demo_expires_at->timezone($tz)->isoFormat('MMM D, YYYY') }}
                        </span>
                    @endif
                @else
                    No
                @endif
            </dd>
        </div>
        @unless ($venueScoped)
            @if ($user->deskCourtClient)
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Desk venue
                    </dt>
                    <dd class="mt-1">
                        <a
                            href="{{ route('admin.court-clients.edit', $user->deskCourtClient) }}"
                            wire:navigate
                            class="font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                        >
                            {{ $user->deskCourtClient->name }}
                        </a>
                    </dd>
                </div>
            @endif
            @if ($user->administeredCourtClient)
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Administers venue
                    </dt>
                    <dd class="mt-1">
                        <a
                            href="{{ route('admin.court-clients.edit', $user->administeredCourtClient) }}"
                            wire:navigate
                            class="font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                        >
                            {{ $user->administeredCourtClient->name }}
                        </a>
                    </dd>
                </div>
            @endif
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                    Team play reminder emails
                </dt>
                <dd class="mt-1 text-zinc-900 dark:text-zinc-100">
                    @if ($user->internal_team_play_reminders_unsubscribed_at)
                        Unsubscribed
                        <span class="block text-xs text-zinc-500">
                            {{ $user->internal_team_play_reminders_unsubscribed_at->timezone($tz)->isoFormat('MMM D, YYYY h:mm a') }}
                        </span>
                    @else
                        Active
                    @endif
                </dd>
            </div>
        @endunless
    </dl>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                @if ($venueScoped)
                    Bookings here (as guest)
                @else
                    Bookings (as guest)
                @endif
            </p>
            <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $stats['bookings_total'] }}</p>
            <p class="mt-0.5 text-xs text-zinc-500">{{ $stats['bookings_upcoming'] }} upcoming</p>
        </div>
        @if ($user->isCoach())
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                    Coached sessions
                </p>
                <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $stats['coached_total'] }}</p>
            </div>
        @endif
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                Open play joins
            </p>
            <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $stats['open_play_total'] }}</p>
        </div>
    </div>

    <section class="space-y-3">
        <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Booking history</h2>
        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50 dark:bg-zinc-900/80">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-zinc-500">When</th>
                        @unless ($venueScoped)
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-zinc-500">Venue</th>
                        @endunless
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-zinc-500">Court</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-zinc-500">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-zinc-500">Amount</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-zinc-500"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
                    @forelse ($bookingsPaginator as $b)
                        <tr wire:key="booking-{{ $b->id }}">
                            <td class="whitespace-nowrap px-4 py-3 text-zinc-800 dark:text-zinc-200">
                                {{ $b->starts_at?->timezone($tz)->isoFormat('MMM D, YYYY · h:mm a') ?? '—' }}
                            </td>
                            @unless ($venueScoped)
                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                    {{ $b->courtClient?->name ?? '—' }}
                                </td>
                            @endunless
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $b->court?->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $this->statusBadgeClasses($b->status) }}"
                                >
                                    {{ Booking::statusDisplayLabel($b->status) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-zinc-700 dark:text-zinc-300">
                                {{ Money::formatMinor($b->amount_cents, $b->currency) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <a
                                    href="{{ $venueScoped ? route('venue.bookings.show', $b) : route('admin.bookings.show', $b) }}"
                                    wire:navigate
                                    class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                                >
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $venueScoped ? 5 : 6 }}" class="px-4 py-8 text-center text-zinc-500">
                                No bookings yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-1">
            {{ $bookingsPaginator->links() }}
        </div>
    </section>

    @if ($user->isCoach() && $coachedBookings->isNotEmpty())
        <section class="space-y-3">
            <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">
                Recent coached sessions
            </h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Latest 20 where this user is assigned as coach
                @if ($venueScoped)
                    at this venue
                @endif
                .
            </p>
            <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                    <thead class="bg-zinc-50 dark:bg-zinc-900/80">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-zinc-500">When</th>
                            @unless ($venueScoped)
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-zinc-500">Venue</th>
                            @endunless
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-zinc-500">Guest</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-zinc-500"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
                        @foreach ($coachedBookings as $b)
                            <tr wire:key="coach-b-{{ $b->id }}">
                                <td class="whitespace-nowrap px-4 py-3 text-zinc-800 dark:text-zinc-200">
                                    {{ $b->starts_at?->timezone($tz)->isoFormat('MMM D, YYYY · h:mm a') ?? '—' }}
                                </td>
                                @unless ($venueScoped)
                                    <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                        {{ $b->courtClient?->name ?? '—' }}
                                    </td>
                                @endunless
                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                    @if ($b->user)
                                        <span class="font-medium">{{ $b->user->name }}</span>
                                        <span class="block text-xs text-zinc-500">{{ $b->user->email }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <a
                                        href="{{ $venueScoped ? route('venue.bookings.show', $b) : route('admin.bookings.show', $b) }}"
                                        wire:navigate
                                        class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                                    >
                                        View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($openPlayParticipants->isNotEmpty())
        <section class="space-y-3">
            <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Open play participation</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Latest 20 join attempts
                @if ($venueScoped)
                    for sessions at this venue
                @endif
                .
            </p>
            <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                    <thead class="bg-zinc-50 dark:bg-zinc-900/80">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-zinc-500">Joined</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-zinc-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-zinc-500">Session</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-zinc-500"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
                        @foreach ($openPlayParticipants as $p)
                            @php
                                $ob = $p->booking;
                            @endphp
                            <tr wire:key="opp-{{ $p->id }}">
                                <td class="whitespace-nowrap px-4 py-3 text-zinc-800 dark:text-zinc-200">
                                    {{ $p->created_at?->timezone($tz)->isoFormat('MMM D, YYYY · h:mm a') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                    {{ str_replace('_', ' ', $p->status) }}
                                </td>
                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                    @if ($ob)
                                        {{ $ob->starts_at?->timezone($tz)->isoFormat('MMM D, YYYY · h:mm a') ?? '—' }}
                                        @unless ($venueScoped)
                                            <span class="block text-xs text-zinc-500">{{ $ob->courtClient?->name ?? '—' }}</span>
                                        @endunless
                                    @else
                                        <span class="text-zinc-400">Booking removed</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    @if ($ob)
                                        <a
                                            href="{{ $venueScoped ? route('venue.bookings.show', $ob) : route('admin.bookings.show', $ob) }}"
                                            wire:navigate
                                            class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                                        >
                                            View
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($activityLogs->isNotEmpty())
        <section class="space-y-3">
            <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Recent activity log</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                @if ($venueScoped)
                    Booking-related activity only — last 30 entries for this user at this venue.
                @else
                    Last 30 entries for this user.
                @endif
            </p>
            <ul class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 bg-white dark:divide-zinc-800 dark:border-zinc-800 dark:bg-zinc-900">
                @foreach ($activityLogs as $log)
                    <li class="px-4 py-3 text-sm" wire:key="log-{{ $log->id }}">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $log->action }}</span>
                            <time
                                class="text-xs text-zinc-500"
                                datetime="{{ $log->created_at?->toIso8601String() }}"
                            >
                                {{ $log->created_at?->timezone($tz)->isoFormat('MMM D, YYYY · h:mm a') }}
                            </time>
                        </div>
                        @if ($log->description)
                            <p class="mt-1 text-zinc-600 dark:text-zinc-400">{{ $log->description }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
