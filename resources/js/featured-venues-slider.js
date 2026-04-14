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

let morphDebounce = null;

function scheduleInitFeaturedVenueSliders() {
    clearTimeout(morphDebounce);
    morphDebounce = setTimeout(() => {
        initFeaturedVenueSliders();
    }, 10);
}

export function registerFeaturedVenueSlidersWithLivewire() {
    document.addEventListener('livewire:init', () => {
        const lw = window.Livewire;
        if (!lw?.hook) {
            return;
        }

        lw.hook('morph.updated', () => {
            scheduleInitFeaturedVenueSliders();
        });
    });
}
