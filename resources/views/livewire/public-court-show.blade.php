@php
    use App\Models\Court;
    use App\Support\Money;

    $c = $court;
    $client = $c->courtClient;
    $rate = $c->effectiveHourlyRateCents();
    $bookBrowseUrl = auth()->check() && ! auth()->user()->usesStaffAppNav()
        ? route('account.book')
        : route('book-now');

    $venuePickTimeUrl = null;
    if ($client) {
        if (auth()->check() && auth()->user()->usesStaffAppNav()) {
            $venuePickTimeUrl = null;
        } elseif (auth()->check()) {
            $venuePickTimeUrl = route('account.book.venue', $client);
        } else {
            $venuePickTimeUrl = route('book-now.venue.book', $client);
        }
    }
@endphp

<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <a
        href="{{ $bookBrowseUrl }}"
        wire:navigate
        class="text-sm font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300"
    >
        ← Back to Book now
    </a>

    <div class="mt-6 space-y-10">
        {{-- Court showcase --}}
        <div class="min-w-0">
            <div class="overflow-hidden rounded-3xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                @php
                    $courtSlides = $c->carouselSlides();
                @endphp
                <x-image-carousel
                    :slides="$courtSlides"
                    :interval="5500"
                    aria-label="Court photos"
                    aspect-class="aspect-[16/9] sm:aspect-[2/1]"
                    class="w-full"
                />
                <div class="p-6 sm:p-8">
                    <div class="flex flex-wrap items-start justify-between gap-3 sm:gap-4">
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                {{ $client?->name ?? 'Venue' }}
                                @if ($client?->city)
                                    · {{ $client->city }}
                                @endif
                            </p>
                            <h1 class="mt-2 font-display text-3xl font-extrabold tracking-tight text-zinc-900 dark:text-white">
                                {{ $c->name }}
                            </h1>
                        </div>
                        <button
                            type="button"
                            wire:click="toggleFavorite"
                            aria-pressed="{{ $courtIsFavorite ? 'true' : 'false' }}"
                            title="{{ $courtIsFavorite ? 'Remove from favorites' : 'Save this court to your favorites' }}"
                            @class([
                                'inline-flex shrink-0 items-center gap-2 rounded-xl border px-3 py-2 text-sm font-semibold shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/40',
                                'border-rose-300 bg-rose-50 text-rose-700 hover:bg-rose-100 dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-100 dark:hover:bg-rose-950/80' => $courtIsFavorite,
                                'border-zinc-200 bg-white text-zinc-700 hover:border-emerald-300 hover:bg-emerald-50/80 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-emerald-700 dark:hover:bg-emerald-950/40' => ! $courtIsFavorite,
                            ])
                        >
                            @if ($courtIsFavorite)
                                <x-app-icon name="heart-solid" class="size-5 text-rose-600 dark:text-rose-400" />
                                <span>Favorited</span>
                            @else
                                <x-app-icon name="heart" class="size-5 text-zinc-500 dark:text-zinc-400" />
                                <span>Favorite</span>
                            @endif
                        </button>
                    </div>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                        <span
                            class="inline-flex rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-bold text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200"
                        >
                            {{ $c->environment === Court::ENV_INDOOR ? 'Indoor' : 'Outdoor' }}
                        </span>
                    </p>
                    @if (public_reviews_enabled() && $client && $client->public_rating_average !== null)
                        <p class="mt-4 inline-flex flex-wrap items-center gap-1 text-base font-semibold text-zinc-800 dark:text-zinc-200">
                            <x-app-icon name="star-solid" class="size-4 text-amber-500 dark:text-amber-400" />
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
                        @if ($this->venueComingSoon)
                            <div
                                class="relative rounded-xl border border-zinc-400/70 bg-zinc-200/90 px-4 py-10 text-center shadow-inner shadow-zinc-950/15 ring-1 ring-zinc-950/10 dark:border-zinc-500 dark:bg-zinc-950 dark:shadow-[inset_0_2px_12px_rgba(0,0,0,0.45)] dark:ring-white/10"
                            >
                                <span
                                    class="pointer-events-none absolute left-1/2 top-6 z-10 -translate-x-1/2 whitespace-nowrap rounded-full bg-zinc-950 px-4 py-2 text-xs font-bold uppercase tracking-wide text-white shadow-xl shadow-black/40 ring-1 ring-white/15"
                                >
                                    Coming soon
                                </span>
                                <p class="relative z-0 mx-auto mt-14 max-w-sm text-sm leading-relaxed text-zinc-700 dark:text-zinc-400">
                                    Online booking isn’t open yet. Explore other venues on Book now, or check back when this club
                                    goes live.
                                </p>
                            </div>
                        @elseif ($venuePickTimeUrl)
                            <a
                                href="{{ $venuePickTimeUrl }}"
                                wire:navigate
                                class="inline-flex w-full flex-col items-center justify-center rounded-xl bg-emerald-600 px-5 py-3 text-center text-sm font-bold text-white shadow-sm hover:bg-emerald-700 sm:w-auto dark:bg-emerald-600 dark:hover:bg-emerald-500"
                            >
                                <span>Proceed to book</span>
                                <span class="mt-0.5 text-xs font-semibold text-emerald-100">{{ $client->name }}</span>
                            </a>
                            <p class="mt-4 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                                Opens the live availability grid for this club (sign in if prompted to complete your request). You
                                can choose this court or another at the same venue.
                            </p>
                        @else
                            <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                                Ready to play? Contact <strong>{{ $client?->name ?? 'the venue' }}</strong> directly to reserve this
                                court, or use your venue portal if you manage this club.
                            </p>
                        @endif
                        @guest
                            @if (public_registration_enabled())
                            <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">
                                <a
                                    href="{{ route('register') }}"
                                    wire:navigate
                                    class="font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                                >
                                    Create a free account
                                </a>
                                to track bookings under My Corner.
                            </p>
                            @endif
                        @endguest
                    </div>
                </div>
            </div>
        </div>

        @if ($client)
            @if (public_reviews_enabled())
                <div id="venue-reviews" class="scroll-mt-24 border-t border-zinc-200 pt-10 dark:border-zinc-800">
                    <livewire:reviews.user-reviews-panel
                        target-type="venue"
                        :target-id="$client->id"
                        :show-heading="false"
                        :key="'ur-public-court-'.$client->id"
                    />
                </div>
            @endif

            <section @class([
                'border-t border-zinc-200 pt-10 dark:border-zinc-800',
                'scroll-mt-24' => ! public_reviews_enabled(),
            ])>
                <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Venue details</h2>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    Location, contact, and amenities.
                </p>
                <div class="mt-4">
                    <x-venue-public-listing :venue="$client" />
                </div>
            </section>
        @endif
    </div>
</div>
