const THEME_KEY = 'pickle-corner-theme';
const LEGACY_THEME_KEY = 'court-booking-theme';

/** @returns {'light'|'dark'|'system'} */
export function getStoredTheme() {
    let v = localStorage.getItem(THEME_KEY);
    if (v === 'light' || v === 'dark' || v === 'system') {
        return v;
    }

    const legacy = localStorage.getItem(LEGACY_THEME_KEY);
    if (legacy === 'light' || legacy === 'dark' || legacy === 'system') {
        localStorage.setItem(THEME_KEY, legacy);
        localStorage.removeItem(LEGACY_THEME_KEY);
        return legacy;
    }

    return 'system';
}

export function isEffectiveDark() {
    const mode = getStoredTheme();
    if (mode === 'dark') {
        return true;
    }
    if (mode === 'light') {
        return false;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches;
}

export function applyStoredTheme() {
    document.documentElement.classList.toggle('dark', isEffectiveDark());
}

/** @param {'light'|'dark'|'system'} mode */
export function setTheme(mode) {
    localStorage.setItem(THEME_KEY, mode);
    applyStoredTheme();
    window.dispatchEvent(new CustomEvent('pickle-corner-theme-changed', { detail: { mode } }));
}

function onSystemPreferenceChange() {
    if (getStoredTheme() === 'system') {
        applyStoredTheme();
    }
}

export function initTheme() {
    applyStoredTheme();

    // wire:navigate morphs the page from server HTML, which has no `dark` on <html>.
    document.addEventListener('livewire:navigated', () => {
        applyStoredTheme();
    });

    window
        .matchMedia('(prefers-color-scheme: dark)')
        .addEventListener('change', onSystemPreferenceChange);

    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-set-theme]');
        if (!trigger) {
            return;
        }
        e.preventDefault();
        const mode = trigger.getAttribute('data-set-theme');
        if (mode !== 'light' && mode !== 'dark' && mode !== 'system') {
            return;
        }
        setTheme(mode);
        trigger.closest('details')?.removeAttribute('open');
    });
}
