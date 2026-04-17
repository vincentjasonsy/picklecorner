import Splide from '@splidejs/splide';
import '@splidejs/splide/css';

const instances = new WeakMap();

function destroySplide(root) {
    const existing = instances.get(root);
    if (existing) {
        try {
            existing.destroy(true);
        } catch {
            // ignore
        }
        instances.delete(root);
    }
}

export function initFeaturedVenueSliders() {
    document.querySelectorAll('[data-featured-venues-slider]').forEach((root) => {
        destroySplide(root);

        const list = root.querySelector('.splide__list');
        if (!list || list.children.length === 0) {
            return;
        }

        const splide = new Splide(root, {
            type: 'slide',
            perPage: 1,
            rewind: true,
            gap: '1rem',
            padding: { right: '12%' },
            pagination: true,
            arrows: true,
            keyboard: 'global',
            drag: true,
            snap: true,
            mediaQuery: 'min',
            breakpoints: {
                640: {
                    perPage: 2,
                    padding: { right: 0 },
                },
                1024: {
                    perPage: 3,
                    padding: { right: 0 },
                },
            },
        });

        splide.mount();
        instances.set(root, splide);
    });
}

/**
 * Photo / hero image carousels (fade, autoplay). Roots use [data-image-splide].
 */
export function initImageSplideCarousels() {
    document.querySelectorAll('[data-image-splide]').forEach((root) => {
        destroySplide(root);

        const list = root.querySelector('.splide__list');
        if (!list || list.children.length === 0) {
            return;
        }

        const intervalRaw = parseInt(root.dataset.splideInterval ?? '6500', 10);
        const autoplay = intervalRaw > 0;

        const splide = new Splide(root, {
            type: 'fade',
            rewind: true,
            autoplay,
            interval: autoplay ? intervalRaw : 6500,
            pauseOnHover: autoplay,
            speed: 500,
            pagination: true,
            arrows: true,
            keyboard: true,
            drag: true,
            easing: 'cubic-bezier(0.25, 1, 0.25, 1)',
        });

        splide.mount();
        instances.set(root, splide);
    });
}

export function initAllSplideSliders() {
    initFeaturedVenueSliders();
    initImageSplideCarousels();
}

let morphDebounce = null;

function scheduleInitAllSplideSliders() {
    clearTimeout(morphDebounce);
    morphDebounce = setTimeout(() => {
        initAllSplideSliders();
    }, 10);
}

export function registerSplideSlidersWithLivewire() {
    document.addEventListener('livewire:init', () => {
        const lw = window.Livewire;
        if (!lw?.hook) {
            return;
        }

        lw.hook('morph.updated', () => {
            scheduleInitAllSplideSliders();
        });
    });
}
