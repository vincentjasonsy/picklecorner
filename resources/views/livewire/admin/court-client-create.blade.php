<div class="mx-auto max-w-4xl space-y-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a
            href="{{ route('admin.court-clients.index') }}"
            wire:navigate
            class="text-sm font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
        >
            ← Back to court clients
        </a>
    </div>

    <div>
        <h1 class="font-display text-2xl font-bold text-zinc-900 dark:text-white">New venue</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
            Creates the venue, assigns a court admin, and adds default weekly hours plus one outdoor and one indoor
            court. You can adjust slot pricing and schedule on the next screen.
        </p>
    </div>

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
                        <div class="sm:col-span-2">
                            <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Internal notes
                            </label>
                            <textarea
                                wire:model="notes"
                                rows="3"
                                class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                            ></textarea>
                        </div>
                        <div class="flex items-center gap-2 sm:col-span-2">
                            <input
                                wire:model="is_active"
                                id="is_active_create"
                                type="checkbox"
                                class="size-4 rounded border-zinc-300 dark:border-zinc-600"
                            />
                            <label for="is_active_create" class="text-sm text-zinc-700 dark:text-zinc-300">
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
                            for="desk_booking_policy_create"
                        >
                            Policy
                        </label>
                        <select
                            wire:model="desk_booking_policy"
                            id="desk_booking_policy_create"
                            class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                        >
                            <option value="{{ \App\Models\CourtClient::DESK_BOOKING_POLICY_MANUAL }}">
                                Manual review
                            </option>
                            <option value="{{ \App\Models\CourtClient::DESK_BOOKING_POLICY_AUTO_APPROVE }}">
                                Auto-confirm
                            </option>
                            <option value="{{ \App\Models\CourtClient::DESK_BOOKING_POLICY_AUTO_DENY }}">
                                Auto-deny
                            </option>
                        </select>
                        @error('desk_booking_policy')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                    <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Court admin</h2>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        One court admin per venue. Only users without a venue yet are listed.
                        <a
                            href="{{ route('admin.users.create') }}"
                            wire:navigate
                            class="font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                        >
                            Create a court admin user
                        </a>
                        if needed.
                    </p>
                    <div class="mt-4">
                        <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Assigned user
                        </label>
                        <select
                            wire:model="admin_user_id"
                            class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                        >
                            <option value="">— Select —</option>
                            @foreach ($courtAdmins as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                        @error('admin_user_id')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Default pricing</h2>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    Venue defaults per hour (Philippine pesos). Courts can override later.
                </p>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2 lg:col-span-1">
                        <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Standard hourly (₱)
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
                    <div class="sm:col-span-2 lg:col-span-1">
                        <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Peak hourly (₱)
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
                    <div class="sm:col-span-2 lg:max-w-xs">
                        <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Currency (ISO)
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
                </div>
            </div>
        </div>

        <div class="border-t border-zinc-200 pt-6 dark:border-zinc-800">
            <button
                type="submit"
                class="font-display rounded-xl bg-emerald-600 px-6 py-3 text-sm font-bold uppercase tracking-wide text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600"
            >
                Create venue
            </button>
        </div>
    </form>
</div>
