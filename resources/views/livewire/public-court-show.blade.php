@php
    use App\Models\Court;
    use App\Support\Money;

    $c = $court;
    $client = $c->courtClient;
    $rate = $c->effectiveHourlyRateCents();
    $bookBrowseUrl = auth()->check() && ! auth()->user()->usesStaffAppNav()
        ? route('account.book')
        : route('book-now');
    $canUseMemberVenueGrid = $client && (! auth()->check() || ! auth()->user()->usesStaffAppNav());
    $venuePickTimeUrl = $client && auth()->check() && ! auth()->user()->usesStaffAppNav()
        ? route('account.book.venue', $client)
        : ($client ? route('book-now.venue.book', $client) : null);
@endphp

<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
    <a
        href="{{ $bookBrowseUrl }}"
        wire:navigate
        class="text-sm font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300"
    >
        ← Back to Book now
    </a>

    <div class="mt-6 overflow-hidden rounded-3xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="relative aspect-[16/9] bg-zinc-100 dark:bg-zinc-800 sm:aspect-[2/1]">
            <img
                src="{{ $c->staticImageUrl() }}"
                alt="{{ $c->name }}"
                class="size-full object-cover object-center"
                loading="eager"
            />
        </div>
        <div class="p-6 sm:p-8">
            <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                {{ $client?->name ?? 'Venue' }}
                @if ($client?->city)
                    · {{ $client->city }}
                @endif
            </p>
            <h1 class="mt-2 font-display text-3xl font-extrabold tracking-tight text-zinc-900 dark:text-white">
                {{ $c->name }}
            </h1>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                <span
                    class="inline-flex rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-bold text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200"
                >
                    {{ $c->environment === Court::ENV_INDOOR ? 'Indoor' : 'Outdoor' }}
                </span>
            </p>
            @if ($client && $client->public_rating_average !== null)
                <p class="mt-4 text-base font-semibold text-zinc-800 dark:text-zinc-200">
                    <span class="text-amber-500 dark:text-amber-400">★</span>
                    {{ number_format((float) $client->public_rating_average, 1) }}
                    @if ($client->public_rating_count > 0)
                        <span class="font-normal text-zinc-500">
                            from {{ number_format($client->public_rating_count) }} reviews
                        </span>
                    @endif
                </p>
            @endif
            @if ($rate !== null)
                <p class="mt-4 font-display text-xl font-bold text-emerald-700 dark:text-emerald-400">
                    {{ Money::formatMinor($rate, $client?->currency) }}
                    <span class="text-base font-medium text-zinc-500 dark:text-zinc-400">/ hr indicative</span>
                </p>
            @endif
            <div class="mt-8 border-t border-zinc-200 pt-6 dark:border-zinc-800">
                @if ($canUseMemberVenueGrid && $venuePickTimeUrl)
                    <a
                        href="{{ $venuePickTimeUrl }}"
                        wire:navigate
                        class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-emerald-700 sm:w-auto dark:bg-emerald-600 dark:hover:bg-emerald-500"
                    >
                        Pick a time at {{ $client->name }}
                    </a>
                    <p class="mt-4 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                        Opens the live availability grid for this club (sign in if prompted). You can choose this court
                        or another at the same venue.
                    </p>
                @else
                    <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                        Ready to play? Contact <strong>{{ $client?->name ?? 'the venue' }}</strong> directly to reserve this
                        court, or use your venue portal if you manage this club.
                    </p>
                @endif
                @guest
                    <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">
                        <a
                            href="{{ route('register') }}"
                            wire:navigate
                            class="font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                        >
                            Create a free account
                        </a>
                        to track bookings under My court.
                    </p>
                @endguest
            </div>
        </div>
    </div>
</div>
