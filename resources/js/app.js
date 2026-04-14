import './bootstrap';
import { initTheme } from './theme';
import { initFeaturedVenueSliders, registerFeaturedVenueSlidersWithLivewire } from './featured-venues-slider';

registerFeaturedVenueSlidersWithLivewire();

document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initFeaturedVenueSliders();
});

document.addEventListener('livewire:navigated', () => {
    initFeaturedVenueSliders();
});
