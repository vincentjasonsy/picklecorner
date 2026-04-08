@props([
    'slides' => [],
    'interval' => 6500,
    'ariaLabel' => 'Photo gallery',
    'aspectClass' => 'aspect-[4/3]',
])

@php
    $slideList = is_array($slides) ? $slides : [];
    $count = count($slideList);
@endphp

@if ($count === 0)
    {{ $slot }}
@elseif ($count === 1)
    @php
        $one = $slideList[0];
    @endphp
    <div {{ $attributes->class(['relative']) }}>
        <div
            class="relative w-full overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-100 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 {{ $aspectClass }}"
        >
            <img
                src="{{ $one['src'] }}"
                alt="{{ $one['alt'] ?? '' }}"
                class="absolute inset-0 size-full object-cover object-center"
                loading="eager"
                decoding="async"
            />
        </div>
    </div>
@else
    <div
        {{ $attributes->class(['relative']) }}
        wire:ignore
        x-cloak
        x-data="{
            n: @js($count),
            i: 0,
            intervalMs: @js((int) $interval),
            timer: null,
            paused: false,
            go(idx) {
                const len = this.n;
                this.i = (idx % len + len) % len;
            },
            next() {
                this.go(this.i + 1);
            },
            prev() {
                this.go(this.i - 1);
            },
            startTimer() {
                clearInterval(this.timer);
                this.timer = setInterval(() => {
                    if (!this.paused) this.next();
                }, this.intervalMs);
            },
            init() {
                this.startTimer();
            },
        }"
        @mouseenter="paused = true"
        @mouseleave="paused = false"
        role="region"
        aria-roledescription="carousel"
        aria-label="{{ $ariaLabel }}"
    >
        <div
            class="relative w-full overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-100 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 {{ $aspectClass }}"
        >
            @foreach ($slideList as $idx => $slide)
                <img
                    x-show="i === {{ $idx }}"
                    x-transition.opacity.duration.500ms
                    src="{{ $slide['src'] }}"
                    alt="{{ $slide['alt'] ?? '' }}"
                    class="absolute inset-0 size-full object-cover object-center"
                    loading="eager"
                    decoding="async"
                />
            @endforeach

            <div
                class="pointer-events-none absolute inset-0 bg-gradient-to-t from-zinc-950/20 via-transparent to-transparent dark:from-zinc-950/35"
                aria-hidden="true"
            ></div>

            <button
                type="button"
                class="pointer-events-auto absolute left-2 top-1/2 z-10 flex size-10 -translate-y-1/2 items-center justify-center rounded-full border border-white/30 bg-zinc-950/40 text-white shadow-md backdrop-blur-sm transition hover:bg-zinc-950/60 sm:left-3 sm:size-11"
                @click="prev(); startTimer();"
                aria-label="Previous photo"
            >
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>
            <button
                type="button"
                class="pointer-events-auto absolute right-2 top-1/2 z-10 flex size-10 -translate-y-1/2 items-center justify-center rounded-full border border-white/30 bg-zinc-950/40 text-white shadow-md backdrop-blur-sm transition hover:bg-zinc-950/60 sm:right-3 sm:size-11"
                @click="next(); startTimer();"
                aria-label="Next photo"
            >
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </button>
        </div>

        <div class="mt-3 flex items-center justify-center gap-2" role="tablist" aria-label="Photo indicators">
            @foreach ($slideList as $idx => $slide)
                <button
                    type="button"
                    class="size-2.5 rounded-full transition sm:size-3 {{ $idx === 0 ? 'bg-emerald-600 dark:bg-emerald-500' : 'bg-zinc-300 dark:bg-zinc-600' }}"
                    :class="i === {{ $idx }} ? 'bg-emerald-600 dark:bg-emerald-500' : 'bg-zinc-300 dark:bg-zinc-600'"
                    @click="go({{ $idx }}); startTimer();"
                    aria-label="Photo {{ $idx + 1 }}"
                ></button>
            @endforeach
        </div>
    </div>
@endif
