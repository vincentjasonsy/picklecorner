<div>
    <section class="border-b border-zinc-200 bg-gradient-to-b from-emerald-950 via-teal-950 to-zinc-950 py-14 dark:border-zinc-800 sm:py-16">
        <div class="mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
            <p class="font-display text-xs font-bold uppercase tracking-[0.25em] text-emerald-300/90">
                {{ config('app.name') }}
            </p>
            <h1 class="font-display mt-4 text-3xl font-bold uppercase tracking-tight text-white sm:text-4xl">
                Contact &amp; book a demo
            </h1>
            <p class="mx-auto mt-4 max-w-xl text-sm leading-relaxed text-emerald-100/90">
                Whether you run a club or you’re just curious how it works, tell us what you need. We’ll follow up by
                email — usually within a business day.
            </p>
            <p class="mx-auto mt-5 max-w-lg text-xs leading-relaxed text-emerald-200/85">
                {{ config('app.name') }} covers public booking, member accounts, venue admin, front desk, coaches, and
                GameQ —
                <a href="{{ url('/#pricing') }}" wire:navigate class="font-semibold text-white underline-offset-2 hover:underline">
                    fees &amp; value
                </a>
                and
                <a href="{{ url('/#tools') }}" wire:navigate class="font-semibold text-white underline-offset-2 hover:underline">
                    everything in the app
                </a>
                .
            </p>
        </div>
    </section>

    <section class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8">
        @if (session('status'))
            <div
                class="mb-8 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-950 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-100"
                role="status"
            >
                {{ session('status') }}
            </div>
        @endif

        <form wire:submit="submit" class="space-y-6">
            <fieldset>
                <legend class="font-display text-sm font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                    What can we help with?
                </legend>
                <div class="mt-3 flex flex-col gap-3 sm:flex-row">
                    <label
                        class="flex cursor-pointer items-center gap-3 rounded-xl border border-zinc-200 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900"
                    >
                        <input
                            wire:model.live="inquiry_type"
                            type="radio"
                            name="inquiry_type"
                            value="demo"
                            class="size-4 border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-950"
                        />
                        <span>
                            <span class="block text-sm font-semibold text-zinc-900 dark:text-white">Book a demo</span>
                            <span class="block text-xs text-zinc-500 dark:text-zinc-400">
                                Walkthrough for venues &amp; staff
                            </span>
                        </span>
                    </label>
                    <label
                        class="flex cursor-pointer items-center gap-3 rounded-xl border border-zinc-200 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900"
                    >
                        <input
                            wire:model.live="inquiry_type"
                            type="radio"
                            name="inquiry_type"
                            value="contact"
                            class="size-4 border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-950"
                        />
                        <span>
                            <span class="block text-sm font-semibold text-zinc-900 dark:text-white">General question</span>
                            <span class="block text-xs text-zinc-500 dark:text-zinc-400">Partnerships, pricing, anything else</span>
                        </span>
                    </label>
                </div>
                @error('inquiry_type')
                    <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </fieldset>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400" for="cp-name">
                        Name <span class="text-red-600">*</span>
                    </label>
                    <input
                        wire:model="name"
                        id="cp-name"
                        type="text"
                        autocomplete="name"
                        class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                    />
                    @error('name')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400" for="cp-email">
                        Email <span class="text-red-600">*</span>
                    </label>
                    <input
                        wire:model="email"
                        id="cp-email"
                        type="email"
                        autocomplete="email"
                        class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                    />
                    @error('email')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400" for="cp-phone">
                        Phone <span class="font-normal normal-case text-zinc-400">(optional)</span>
                    </label>
                    <input
                        wire:model="phone"
                        id="cp-phone"
                        type="tel"
                        autocomplete="tel"
                        class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                    />
                    @error('phone')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400" for="cp-club">
                        Club / venue <span class="font-normal normal-case text-zinc-400">(optional)</span>
                    </label>
                    <input
                        wire:model="club_name"
                        id="cp-club"
                        type="text"
                        class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                    />
                    @error('club_name')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400" for="cp-msg">
                        Message <span class="text-red-600">*</span>
                    </label>
                    <textarea
                        wire:model="message"
                        id="cp-msg"
                        rows="5"
                        class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                        placeholder="Tell us about your venue, preferred times for a call, or your question."
                    ></textarea>
                    @error('message')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex justify-end border-t border-zinc-200 pt-6 dark:border-zinc-800">
                <button
                    type="submit"
                    class="font-display inline-flex min-w-[12rem] items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-3 text-sm font-bold uppercase tracking-wide text-white shadow-md transition hover:from-emerald-500 hover:to-teal-500 focus:outline-none focus-visible:ring-4 focus-visible:ring-emerald-500/40 disabled:opacity-60 disabled:pointer-events-none"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="submit">Send message</span>
                    <span wire:loading wire:target="submit" class="inline-flex items-center gap-2">
                        <svg
                            class="size-4 shrink-0 animate-spin text-white/90"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        Sending…
                    </span>
                </button>
            </div>
        </form>
    </section>
</div>
