<div>
    <div
        class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-8 shadow-xl shadow-zinc-900/5 dark:border-zinc-800 dark:bg-zinc-900/80 dark:shadow-none"
    >
        @if ($demo)
            <div
                class="mb-6 rounded-2xl border border-amber-200/80 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100"
                role="status"
            >
                <p class="font-semibold">Demo account</p>
                <p class="mt-1 leading-relaxed opacity-90">
                    Your bookings and saved data are removed automatically after
                    {{ config('demo.ttl_hours') }}
                    {{ \Illuminate\Support\Str::plural('hour', config('demo.ttl_hours')) }}.
                    For a permanent account, use
                    <a href="{{ route('register') }}" wire:navigate class="font-semibold underline underline-offset-2">full registration</a>.
                </p>
            </div>
        @endif

        <div>
            <h2 class="font-display text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                @if ($demo)
                    Try the site
                @else
                    Join the club
                @endif
            </h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                @if ($demo)
                    Create a temporary player account to explore booking and tools.
                @else
                    Create your player account and start booking courts in minutes.
                @endif
            </p>
        </div>

        <form wire:submit="register" class="mt-8 space-y-5">
            <div>
                <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                    Full name
                </label>
                <input
                    wire:model="name"
                    id="name"
                    type="text"
                    autocomplete="name"
                    required
                    class="mt-1.5 block w-full rounded-xl border border-zinc-200 bg-zinc-50/80 px-4 py-3 text-sm text-zinc-900 outline-none ring-emerald-500/40 transition placeholder:text-zinc-400 focus:border-emerald-500 focus:bg-white focus:ring-4 dark:border-zinc-700 dark:bg-zinc-950/50 dark:text-zinc-100 dark:focus:border-emerald-400 dark:focus:bg-zinc-950"
                    placeholder="Alex Johnson"
                />
                @error('name')
                    <p class="mt-1.5 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                    Email
                </label>
                <input
                    wire:model="email"
                    id="email"
                    type="email"
                    autocomplete="email"
                    required
                    class="mt-1.5 block w-full rounded-xl border border-zinc-200 bg-zinc-50/80 px-4 py-3 text-sm text-zinc-900 outline-none ring-emerald-500/40 transition placeholder:text-zinc-400 focus:border-emerald-500 focus:bg-white focus:ring-4 dark:border-zinc-700 dark:bg-zinc-950/50 dark:text-zinc-100 dark:focus:border-emerald-400 dark:focus:bg-zinc-950"
                    placeholder="you@example.com"
                />
                @error('email')
                    <p class="mt-1.5 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label
                    for="password"
                    class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                >
                    Password
                </label>
                <input
                    wire:model="password"
                    id="password"
                    type="password"
                    autocomplete="new-password"
                    required
                    class="mt-1.5 block w-full rounded-xl border border-zinc-200 bg-zinc-50/80 px-4 py-3 text-sm text-zinc-900 outline-none ring-emerald-500/40 transition placeholder:text-zinc-400 focus:border-emerald-500 focus:bg-white focus:ring-4 dark:border-zinc-700 dark:bg-zinc-950/50 dark:text-zinc-100 dark:focus:border-emerald-400 dark:focus:bg-zinc-950"
                    placeholder="Min. 8 characters"
                />
                @error('password')
                    <p class="mt-1.5 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label
                    for="password_confirmation"
                    class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                >
                    Confirm password
                </label>
                <input
                    wire:model="password_confirmation"
                    id="password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    required
                    class="mt-1.5 block w-full rounded-xl border border-zinc-200 bg-zinc-50/80 px-4 py-3 text-sm text-zinc-900 outline-none ring-emerald-500/40 transition placeholder:text-zinc-400 focus:border-emerald-500 focus:bg-white focus:ring-4 dark:border-zinc-700 dark:bg-zinc-950/50 dark:text-zinc-100 dark:focus:border-emerald-400 dark:focus:bg-zinc-950"
                    placeholder="Repeat password"
                />
            </div>

            <div class="rounded-2xl border border-zinc-200/90 bg-zinc-50/80 p-4 dark:border-zinc-700 dark:bg-zinc-950/40">
                <p class="font-display text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                    Data privacy (Philippines)
                </p>
                <p class="mt-2 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">
                    Under the Data Privacy Act of 2012 (Republic Act No. 10173), we need your informed consent to process
                    your personal data for your account. Marketing emails are optional and separate.
                </p>
                <label class="mt-4 flex cursor-pointer gap-3">
                    <input
                        wire:model="accept_privacy"
                        type="checkbox"
                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500/40 dark:border-zinc-600 dark:bg-zinc-900"
                    />
                    <span class="text-sm leading-snug text-zinc-700 dark:text-zinc-300">
                        I have read and agree to the
                        <a
                            href="{{ route('privacy-policy') }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="font-semibold text-emerald-700 underline underline-offset-2 hover:text-emerald-600 dark:text-emerald-400 dark:hover:text-emerald-300"
                        >
                            Privacy Policy
                        </a>
                        (version {{ config('data_privacy.policy_version') }}) and consent to the collection, use, storage,
                        and processing of my personal data as described there, in accordance with Philippine law.
                        <span class="text-red-600 dark:text-red-400">*</span>
                    </span>
                </label>
                @error('accept_privacy')
                    <p class="mt-2 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

                <label class="mt-4 flex cursor-pointer gap-3 border-t border-zinc-200/80 pt-4 dark:border-zinc-700/80">
                    <input
                        wire:model="subscribe_marketing_emails"
                        type="checkbox"
                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500/40 dark:border-zinc-600 dark:bg-zinc-900"
                    />
                    <span class="text-sm leading-snug text-zinc-700 dark:text-zinc-300">
                        I agree to receive occasional emails about product updates, tips, and promotional offers from
                        {{ config('app.name') }}. I understand I can unsubscribe at any time. (Optional)
                    </span>
                </label>
                <p class="mt-4 text-xs leading-relaxed text-zinc-500 dark:text-zinc-500">
                    Other legal documents:
                    <a
                        href="{{ route('terms') }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-semibold text-emerald-700 underline-offset-2 hover:underline dark:text-emerald-400"
                    >
                        Terms &amp; conditions
                    </a>,
                    <a
                        href="{{ route('refund-policy') }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-semibold text-emerald-700 underline-offset-2 hover:underline dark:text-emerald-400"
                    >
                        Refund policy
                    </a>,
                    <a
                        href="{{ route('booking-cancellation-policy') }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-semibold text-emerald-700 underline-offset-2 hover:underline dark:text-emerald-400"
                    >
                        Booking &amp; cancellation
                    </a>.
                </p>
            </div>

            <button
                type="submit"
                class="font-display flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-3.5 text-sm font-bold uppercase tracking-wide text-white shadow-lg shadow-emerald-900/25 transition hover:from-emerald-500 hover:to-teal-500 focus:outline-none focus-visible:ring-4 focus-visible:ring-emerald-500/40 active:scale-[0.99] disabled:opacity-60 dark:shadow-emerald-950/40"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="register">
                    @if ($demo)
                        Start demo
                    @else
                        Create account
                    @endif
                </span>
                <span wire:loading wire:target="register">Creating…</span>
            </button>
        </form>

        <p class="mt-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
            Already have an account?
            <a
                href="{{ route('login') }}"
                wire:navigate
                class="font-semibold text-emerald-600 hover:text-emerald-500 dark:text-emerald-400 dark:hover:text-emerald-300"
            >
                Sign in
            </a>
        </p>

    </div>
</div>
