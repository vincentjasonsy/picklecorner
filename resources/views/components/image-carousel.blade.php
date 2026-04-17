@props([
    'slides' => [],
    'interval' => 6500,
    'ariaLabel' => 'Photo gallery',
    'aspectClass' => 'aspect-[4/3]',
    'gradientOverlayClass' => 'from-zinc-950/20 via-transparent to-transparent dark:from-zinc-950/35',
    'showGradient' => true,
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
    <div {{ $attributes->class(['relative']) }}>
        <div
            class="relative w-full overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-100 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 {{ $aspectClass }}"
        >
            <section
                class="splide splide--image-carousel image-splide absolute inset-0 h-full w-full"
                data-image-splide
                wire:ignore
                data-splide-interval="{{ (int) $interval }}"
                role="region"
                aria-roledescription="carousel"
                aria-label="{{ $ariaLabel }}"
            >
                <div class="splide__track h-full">
                    <ul class="splide__list h-full">
                        @foreach ($slideList as $slide)
                            <li class="splide__slide h-full">
                                <img
                                    src="{{ $slide['src'] }}"
                                    alt="{{ $slide['alt'] ?? '' }}"
                                    class="size-full object-cover object-center"
                                    loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                    decoding="async"
                                />
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>
            @if ($showGradient)
                <div
                    class="pointer-events-none absolute inset-0 z-[2] bg-gradient-to-t {{ $gradientOverlayClass }}"
                    aria-hidden="true"
                ></div>
            @endif
        </div>
    </div>
@endif
