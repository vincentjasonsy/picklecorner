<div>
    @if ($open && $copy !== null)
    <div
        class="fixed inset-0 z-[100] flex items-end justify-center p-4 sm:items-center"
        role="dialog"
        aria-modal="true"
        aria-labelledby="member-booking-nudge-title"
        wire:key="member-booking-nudge"
    >
        <div
            class="absolute inset-0 bg-zinc-900/55 backdrop-blur-[2px] dark:bg-black/65"
            wire:click="dismiss"
        ></div>

        <div
            class="relative w-full max-w-md overflow-hidden rounded-2xl border border-emerald-200/90 bg-white shadow-2xl shadow-emerald-900/15 dark:border-emerald-800/60 dark:bg-zinc-900"
            @click.stop
        >
            <div
                class="bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-600 px-5 py-4 text-white dark:from-emerald-800 dark:via-teal-900 dark:to-cyan-950"
            >
                <p class="text-xs font-bold uppercase tracking-widest text-emerald-100/90">Pickle reminder</p>
                <h2 id="member-booking-nudge-title" class="mt-1 font-display text-xl font-extrabold tracking-tight">
                    {{ $copy['headline'] }}
                </h2>
            </div>

            <div class="space-y-4 px-5 py-5">
                <p class="text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">
                    {{ $copy['body'] }}
                </p>
                <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        wire:click="dismiss"
                        class="order-2 rounded-xl border border-zinc-200 px-4 py-2.5 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                    >
                        Not now
                    </button>
                    <button
                        type="button"
                        wire:click="goBook"
                        class="order-1 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2.5 text-sm font-bold text-white shadow-md shadow-emerald-900/20 transition hover:from-emerald-500 hover:to-teal-500 sm:order-2"
                    >
                        Book now
                    </button>
                </div>
                <p class="text-center text-xs text-zinc-500 dark:text-zinc-400">
                    We’ll wait a few days before nudging again if you dismiss this.
                </p>
            </div>

            <button
                type="button"
                wire:click="dismiss"
                class="absolute right-3 top-3 rounded-lg p-1 text-white/90 transition hover:bg-white/10"
                aria-label="Close"
            >
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
    @endif
</div>
