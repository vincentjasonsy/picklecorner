import './bootstrap';
import { initTheme } from './theme';
import { initAllSplideSliders, registerSplideSlidersWithLivewire } from './splide-sliders';

registerSplideSlidersWithLivewire();

document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initAllSplideSliders();
});

document.addEventListener('livewire:navigated', () => {
    initAllSplideSliders();
});
