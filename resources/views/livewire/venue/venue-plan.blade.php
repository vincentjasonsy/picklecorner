<div class="mx-auto max-w-3xl space-y-8">
    <div>
        <h1 class="font-display text-2xl font-bold text-zinc-900 dark:text-white">Plan &amp; billing</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
            {{ $courtClient->name }} — subscription tiers for venue owners using {{ config('app.name') }}.
        </p>
    </div>

    <div
        class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900"
        aria-live="polite"
    >
        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Current plan</p>
        @if ($courtClient->hasPremiumSubscription())
            <p class="mt-2 font-display text-xl font-bold text-emerald-700 dark:text-emerald-400">Premium</p>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                You have access to gift cards and the customer CRM. Thank you for subscribing.
            </p>
        @else
            <p class="mt-2 font-display text-xl font-bold text-zinc-900 dark:text-white">Basic</p>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                Core venue tools are included. Upgrade to Premium to sell gift cards and use customer notes &amp; CRM.
            </p>
        @endif
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Compare tiers</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <p class="font-display font-bold text-zinc-900 dark:text-white">Basic</p>
                <ul class="mt-3 list-inside list-disc space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                    <li>Manual booking &amp; booking history</li>
                    <li>Desk request queue &amp; policies</li>
                    <li>Venue settings, courts, reports</li>
                </ul>
            </div>
            <div class="rounded-lg border border-emerald-200 bg-emerald-50/60 p-4 dark:border-emerald-900/60 dark:bg-emerald-950/30">
                <p class="font-display font-bold text-emerald-900 dark:text-emerald-200">Premium</p>
                <p class="mt-1 text-xs font-medium text-emerald-800 dark:text-emerald-300">Everything in Basic, plus:</p>
                <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-emerald-900/90 dark:text-emerald-200/90">
                    <li>Gift cards — issue and track codes</li>
                    <li>Customer CRM — search players, notes, history</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-6 dark:border-zinc-600 dark:bg-zinc-900/50">
        <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Upgrade or change plan</h2>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
            Billing and upgrades are handled by the {{ config('app.name') }} team. When you connect payments (Stripe or
            similar), this page can link to checkout — for now, reach out to enable Premium for your venue.
        </p>
        @php
            $ownerEmail = config('mail.from.address');
        @endphp
        @if (is_string($ownerEmail) && $ownerEmail !== '')
            <a
                href="mailto:{{ $ownerEmail }}?subject={{ rawurlencode(config('app.name').' — Premium plan') }}"
                class="mt-4 inline-flex rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600"
            >
                Email us to upgrade
            </a>
        @endif
    </div>
</div>
