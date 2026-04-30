<style>
    [x-cloak] {
        display: none !important;
    }
</style>

<div
    x-data="{
        open: false,
        platform: 'other',
        installed: false,
        init() {
            const ua = (navigator.userAgent || '').toLowerCase();
            const isiOS = /iphone|ipad|ipod/.test(ua);
            const isAndroid = ua.includes('android');
            this.platform = isiOS ? 'ios' : (isAndroid ? 'android' : 'other');
            this.installed = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        },
    }"
>
    <button
        type="button"
        x-cloak
        x-show="!installed"
        @click="open = true"
        class="inline-flex min-h-[44px] items-center rounded-2xl border border-zinc-200/90 bg-white px-2.5 text-[11px] font-semibold text-zinc-700 shadow-sm transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800/80"
    >
        Install app
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition.opacity
        @keydown.escape.window="open = false"
        class="fixed inset-0 z-50 flex items-end justify-center bg-zinc-900/60 p-0 backdrop-blur-[1px] sm:items-center sm:p-4"
        @click.self="open = false"
    >
        <div class="w-full max-w-md rounded-t-3xl border border-zinc-200 bg-white p-4 shadow-xl dark:border-zinc-700 dark:bg-zinc-900 sm:rounded-3xl sm:p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="font-display text-lg font-extrabold text-zinc-900 dark:text-white">Install as app</p>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Keep GameQ on your home screen for quick access.</p>
                </div>
                <button
                    type="button"
                    @click="open = false"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-zinc-500 transition hover:bg-zinc-100 dark:hover:bg-zinc-800"
                    aria-label="Close install guide"
                >
                    ✕
                </button>
            </div>

            <div class="mt-4 space-y-3 text-sm text-zinc-700 dark:text-zinc-200">
                <div x-show="platform === 'ios'" x-cloak class="rounded-xl border border-zinc-200/80 bg-zinc-50/70 p-3 dark:border-zinc-700 dark:bg-zinc-950/60">
                    <p class="text-xs font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">iPhone / iPad (Safari)</p>
                    <ol class="mt-2 list-decimal space-y-1.5 pl-4 text-[13px] leading-relaxed">
                        <li>Tap the <span class="font-semibold">Share</span> icon in Safari.</li>
                        <li>Choose <span class="font-semibold">Add to Home Screen</span>.</li>
                        <li>Tap <span class="font-semibold">Add</span>.</li>
                    </ol>
                </div>

                <div x-show="platform === 'android'" x-cloak class="rounded-xl border border-zinc-200/80 bg-zinc-50/70 p-3 dark:border-zinc-700 dark:bg-zinc-950/60">
                    <p class="text-xs font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Android (Chrome)</p>
                    <ol class="mt-2 list-decimal space-y-1.5 pl-4 text-[13px] leading-relaxed">
                        <li>Tap the browser <span class="font-semibold">menu</span>.</li>
                        <li>Select <span class="font-semibold">Install app</span> or <span class="font-semibold">Add to Home screen</span>.</li>
                        <li>Confirm <span class="font-semibold">Install</span>.</li>
                    </ol>
                </div>

                <div x-show="platform === 'other'" x-cloak class="rounded-xl border border-zinc-200/80 bg-zinc-50/70 p-3 dark:border-zinc-700 dark:bg-zinc-950/60">
                    <p class="text-xs font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Browser install</p>
                    <ol class="mt-2 list-decimal space-y-1.5 pl-4 text-[13px] leading-relaxed">
                        <li>Open your browser menu.</li>
                        <li>Pick <span class="font-semibold">Install app</span> or <span class="font-semibold">Add to home screen</span>.</li>
                        <li>Confirm to pin GameQ as an app.</li>
                    </ol>
                </div>
            </div>

            <button
                type="button"
                @click="open = false"
                class="mt-4 w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-500 active:scale-[0.99]"
            >
                Got it
            </button>
        </div>
    </div>
</div>
