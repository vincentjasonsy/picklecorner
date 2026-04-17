@props([
    'interval' => 6500,
])

@php
    $slides = [
        [
            'src' => asset('images/slider/slide-1.jpg'),
            'alt' => 'Outdoor pickleball courts under open sky',
        ],
        [
            'src' => asset('images/slider/slide-2.jpg'),
            'alt' => 'Indoor court booking',
        ],
        [
            'src' => asset('images/slider/slide-3.jpg'),
            'alt' => 'Your club in one app',
        ],
    ];
@endphp

<x-image-carousel
    :slides="$slides"
    :interval="$interval"
    aspect-class="aspect-[5/2] sm:aspect-[21/9]"
    aria-label="Featured visuals"
    gradient-overlay-class="from-zinc-950/25 via-transparent to-transparent dark:from-zinc-950/40"
    {{ $attributes }}
/>
