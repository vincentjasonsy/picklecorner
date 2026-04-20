<div class="w-full space-y-6">
    <a
        href="{{ route('admin.users.index') }}"
        wire:navigate
        class="inline-block text-sm font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
    >
        ← Back to users
    </a>

    <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="font-display text-2xl font-bold text-zinc-900 dark:text-white">{{ $heading }}</h1>
            <p class="mt-1 max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
                @if ($isEdit)
                    Update profile, role, or password. Leave password blank to keep the current one.
                @else
                    Add a user account and assign a role. They can sign in with the email and password you set.
                @endif
            </p>
        </div>
        @if ($isEdit && $user)
            <a
                href="{{ route('admin.users.summary', $user) }}"
                wire:navigate
                class="shrink-0 rounded-lg border border-zinc-200 px-3 py-1.5 text-sm font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
            >
                View summary
            </a>
        @endif
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="grid gap-6 lg:grid-cols-2 lg:items-start">
            <div
                class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900"
            >
                <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Account</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2 lg:col-span-1">
                        <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Name
                        </label>
                        <input
                            wire:model="name"
                            type="text"
                            autocomplete="name"
                            class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                        />
                        @error('name')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-2 lg:col-span-1">
                        <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Email
                        </label>
                        <input
                            wire:model="email"
                            type="email"
                            autocomplete="email"
                            class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                        />
                        @error('email')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Role
                        </label>
                        <select
                            wire:model.live="user_type_id"
                            class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 lg:max-w-md"
                        >
                            @foreach ($typeOptions as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                        @error('user_type_id')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    @if ($deskUserTypeId !== '' && (string) $user_type_id === (string) $deskUserTypeId)
                        <div class="sm:col-span-2">
                            <label
                                class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                            >
                                Venue (desk)
                            </label>
                            <select
                                wire:model="desk_court_client_id"
                                class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 lg:max-w-md"
                            >
                                <option value="">Select venue</option>
                                @foreach ($courtClientOptions as $cc)
                                    <option value="{{ $cc->id }}">{{ $cc->name }}</option>
                                @endforeach
                            </select>
                            @error('desk_court_client_id')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                Desk accounts are scoped to this court client for future front-desk tools.
                            </p>
                        </div>
                    @endif

                    @if (
                        $giftSubscriptionControlsVisible
                        && $isEdit
                        && $courtAdminTypeId !== ''
                        && (string) $user_type_id === (string) $courtAdminTypeId
                    )
                        @if ($user?->administeredCourtClient)
                            <div class="sm:col-span-2 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                                <h3 class="font-display text-sm font-bold text-zinc-900 dark:text-white">
                                    Venue subscription
                                </h3>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                    Applies to
                                    <strong class="font-medium text-zinc-700 dark:text-zinc-300">
                                        {{ $user->administeredCourtClient->name }}
                                    </strong>
                                    — controls gift cards and customer CRM in the venue portal (Basic vs Premium).
                                </p>
                                <label
                                    class="mt-3 block text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                                    for="venue_subscription_tier"
                                >
                                    Tier
                                </label>
                                <select
                                    wire:model="venue_subscription_tier"
                                    id="venue_subscription_tier"
                                    class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 lg:max-w-md"
                                >
                                    <option value="{{ \App\Models\CourtClient::TIER_BASIC }}">
                                        Basic — core operations only
                                    </option>
                                    <option value="{{ \App\Models\CourtClient::TIER_PREMIUM }}">
                                        Premium — gift cards &amp; CRM
                                    </option>
                                </select>
                                @error('venue_subscription_tier')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        @else
                            <div class="sm:col-span-2 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    <strong class="font-medium text-zinc-700 dark:text-zinc-300">Venue subscription</strong>
                                    — assign this user to a venue under
                                    <a
                                        href="{{ route('admin.court-clients.index') }}"
                                        wire:navigate
                                        class="font-semibold text-emerald-600 hover:underline dark:text-emerald-400"
                                    >
                                        Court clients
                                    </a>
                                    first; then you can set Basic or Premium here.
                                </p>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            <div
                class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900"
            >
                <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Password</h2>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    @if ($isEdit)
                        Optional. Fill both fields only when you want to reset this user’s password.
                    @else
                        Required. Share this password securely with the user; they can change it after signing in.
                    @endif
                </p>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2 lg:col-span-1">
                        <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Password
                        </label>
                        <input
                            wire:model="password"
                            type="password"
                            autocomplete="new-password"
                            class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                        />
                        @error('password')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-2 lg:col-span-1">
                        <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Confirm password
                        </label>
                        <input
                            wire:model="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 border-t border-zinc-200 pt-6 dark:border-zinc-800">
            <button
                type="submit"
                class="font-display rounded-xl bg-emerald-600 px-6 py-3 text-sm font-bold uppercase tracking-wide text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600"
            >
                {{ $isEdit ? 'Save changes' : 'Create user' }}
            </button>
            <a
                href="{{ route('admin.users.index') }}"
                wire:navigate
                class="text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100"
            >
                Cancel
            </a>
        </div>
    </form>
</div>
