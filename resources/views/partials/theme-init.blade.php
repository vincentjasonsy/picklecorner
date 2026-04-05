{{-- Apply theme before paint to avoid flash; keep in sync with resources/js/theme.js --}}
<script>
    (function () {
        const k = 'pickle-corner-theme';
        const legacy = 'court-booking-theme';
        let m = localStorage.getItem(k);
        if (m !== 'light' && m !== 'dark' && m !== 'system') {
            const old = localStorage.getItem(legacy);
            if (old === 'light' || old === 'dark' || old === 'system') {
                localStorage.setItem(k, old);
                localStorage.removeItem(legacy);
                m = old;
            } else {
                m = 'system';
            }
        }
        const dark =
            m === 'dark' ||
            (m === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.classList.toggle('dark', dark);
    })();
</script>
