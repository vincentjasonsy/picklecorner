<div class="mx-auto max-w-4xl space-y-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a
            href="{{ route('admin.court-clients.index') }}"
            wire:navigate
            class="text-sm font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
        >
            ← Court clients
        </a>
    </div>

    <div>
        <h1 class="font-display text-2xl font-bold text-zinc-900 dark:text-white">Quick venue setup</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
            Create a new venue, its court admin login, and optionally a front-desk account in one step. Default weekly
            hours and two courts (outdoor + indoor) are added automatically — same as
            <a
                href="{{ route('admin.court-clients.create') }}"
                wire:navigate
                class="font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
            >
                New venue
            </a>
            , but without picking existing users.
        </p>
    </div>

    @if ($setupComplete && $createdVenueId)
        <div
            class="space-y-4 rounded-xl border border-emerald-200 bg-emerald-50/90 p-6 dark:border-emerald-900/50 dark:bg-emerald-950/35"
            role="status"
        >
            <h2 class="font-display text-lg font-bold text-emerald-950 dark:text-emerald-100">
                {{ $createdVenueName }} is ready
            </h2>
            <p class="text-sm text-emerald-950/90 dark:text-emerald-100/90">
                Copy the passwords below now. They are not stored in plain text and will disappear if you leave this
                page.
            </p>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-lg border border-emerald-200/80 bg-white/90 p-4 dark:border-emerald-800/60 dark:bg-zinc-900/80">
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">
                        Court admin
                    </p>
                    <p class="mt-2 font-mono text-sm text-zinc-900 dark:text-zinc-100">{{ $createdAdminEmail }}</p>
                    <p class="mt-1 font-mono text-sm text-zinc-700 dark:text-zinc-300">
                        {{ $createdAdminPasswordPlain }}
                    </p>
                </div>
                @if ($createdDeskAccount && $createdDeskEmail !== '')
                    <div
                        class="rounded-lg border border-emerald-200/80 bg-white/90 p-4 dark:border-emerald-800/60 dark:bg-zinc-900/80"
                    >
                        <p class="text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">
                            Desk
                        </p>
                        <p class="mt-2 font-mono text-sm text-zinc-900 dark:text-zinc-100">{{ $createdDeskEmail }}</p>
                        <p class="mt-1 font-mono text-sm text-zinc-700 dark:text-zinc-300">
                            {{ $createdDeskPasswordPlain }}
                        </p>
                    </div>
                @endif
            </div>
            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ route('admin.court-clients.edit', $createdVenueId) }}"
                    wire:navigate
                    class="inline-flex items-center rounded-lg bg-emerald-700 px-4 py-2 text-sm font-bold uppercase tracking-wide text-white hover:bg-emerald-800 dark:bg-emerald-600 dark:hover:bg-emerald-500"
                >
                    Open venue settings
                </a>
                <button
                    type="button"
                    wire:click="startAnother"
                    class="inline-flex items-center rounded-lg border border-emerald-700/40 bg-white px-4 py-2 text-sm font-semibold text-emerald-900 hover:bg-emerald-50 dark:border-emerald-600/50 dark:bg-zinc-900 dark:text-emerald-100 dark:hover:bg-zinc-800"
                >
                    Create another venue
                </button>
            </div>
        </div>
    @endif

    @if (! $setupComplete)
        <form wire:submit="save" class="space-y-6">
            <div class="grid gap-6 lg:grid-cols-2 lg:items-start">
                <div class="space-y-6">
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                        <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Venue</h2>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    Name
                                </label>
                                <input
                                    wire:model="name"
                                    type="text"
                                    class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                />
                                @error('name')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    URL slug (optional)
                                </label>
                                <input
                                    wire:model="slug"
                                    type="text"
                                    placeholder="Leave blank to generate from name"
                                    class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                />
                                @error('slug')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    City
                                </label>
                                <input
                                    wire:model="city"
                                    type="text"
                                    class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                />
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    Currency
                                </label>
                                <input
                                    wire:model="currency"
                                    type="text"
                                    maxlength="3"
                                    class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm uppercase dark:border-zinc-700 dark:bg-zinc-950"
                                />
                                @error('currency')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    Default hourly (optional)
                                </label>
                                <input
                                    wire:model="hourly_rate_pesos"
                                    type="text"
                                    inputmode="decimal"
                                    placeholder="e.g. 350"
                                    class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                />
                                @error('hourly_rate_pesos')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    Peak hourly (optional)
                                </label>
                                <input
                                    wire:model="peak_hourly_rate_pesos"
                                    type="text"
                                    inputmode="decimal"
                                    placeholder="e.g. 500"
                                    class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                />
                                @error('peak_hourly_rate_pesos')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    Internal notes
                                </label>
                                <textarea
                                    wire:model="notes"
                                    rows="2"
                                    class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                ></textarea>
                            </div>
                            <div class="flex items-center gap-2 sm:col-span-2">
                                <input
                                    wire:model="is_active"
                                    id="vqs_is_active"
                                    type="checkbox"
                                    class="size-4 rounded border-zinc-300 dark:border-zinc-600"
                                />
                                <label for="vqs_is_active" class="text-sm text-zinc-700 dark:text-zinc-300">
                                    Venue is active
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                        <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">
                            Desk manual booking requests
                        </h2>
                        <div class="mt-4">
                            <label
                                class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                                for="vqs_desk_policy"
                            >
                                Policy
                            </label>
                            <select
                                wire:model="desk_booking_policy"
                                id="vqs_desk_policy"
                                class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                            >
                                @foreach (\App\Models\CourtClient::deskBookingPolicyValues() as $v)
                                    <option value="{{ $v }}">{{ str_replace('_', ' ', $v) }}</option>
                                @endforeach
                            </select>
                            @error('desk_booking_policy')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                        <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Court admin account</h2>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            New user with venue manager access (/venue).
                        </p>
                        <div class="mt-4 grid gap-4">
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    Name
                                </label>
                                <input
                                    wire:model="admin_name"
                                    type="text"
                                    autocomplete="off"
                                    class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                />
                                @error('admin_name')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    Email
                                </label>
                                <input
                                    wire:model="admin_email"
                                    type="email"
                                    autocomplete="off"
                                    class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                />
                                @error('admin_email')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    Password
                                </label>
                                <input
                                    wire:model="admin_password"
                                    type="password"
                                    autocomplete="new-password"
                                    class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                />
                                @error('admin_password')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    Confirm password
                                </label>
                                <input
                                    wire:model="admin_password_confirmation"
                                    type="password"
                                    autocomplete="new-password"
                                    class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Desk account</h2>
                            <label class="inline-flex cursor-pointer items-center gap-2">
                                <input
                                    wire:model.live="create_desk_account"
                                    type="checkbox"
                                    class="size-4 rounded border-zinc-300 dark:border-zinc-600"
                                />
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Create desk user</span>
                            </label>
                        </div>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            Front-desk staff (/desk) for this venue only.
                        </p>
                        @if ($create_desk_account)
                            <div class="mt-4 grid gap-4">
                                <div>
                                    <label
                                        class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                                    >
                                        Name
                                    </label>
                                    <input
                                        wire:model="desk_name"
                                        type="text"
                                        autocomplete="off"
                                        class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                    />
                                    @error('desk_name')
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label
                                        class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                                    >
                                        Email
                                    </label>
                                    <input
                                        wire:model="desk_email"
                                        type="email"
                                        autocomplete="off"
                                        class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                    />
                                    @error('desk_email')
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label
                                        class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                                    >
                                        Password
                                    </label>
                                    <input
                                        wire:model="desk_password"
                                        type="password"
                                        autocomplete="new-password"
                                        class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                    />
                                    @error('desk_password')
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label
                                        class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                                    >
                                        Confirm password
                                    </label>
                                    <input
                                        wire:model="desk_password_confirmation"
                                        type="password"
                                        autocomplete="new-password"
                                        class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                    />
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-3">
                <button
                    type="submit"
                    class="rounded-lg bg-zinc-900 px-6 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white"
                >
                    Create venue &amp; accounts
                </button>
            </div>
        </form>
    @endif
</div>
