<div class="space-y-10">
    <div>
        <h1 class="font-display text-2xl font-extrabold tracking-tight text-zinc-900 dark:text-white">Profile</h1>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
            Keep your details fresh — we’ll use this name across bookings and emails.
        </p>
    </div>

    <section
        class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80 sm:p-8"
    >
        <div class="flex items-center gap-2">
            <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Basics</h2>
            <span class="text-xl" aria-hidden="true">✨</span>
        </div>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Name and login email</p>

        <form wire:submit="saveProfile" class="mt-6 space-y-5">
            <div>
                <label
                    for="member-name"
                    class="block text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                >
                    Display name
                </label>
                <input
                    wire:model="name"
                    id="member-name"
                    type="text"
                    autocomplete="name"
                    required
                    class="mt-1.5 block w-full rounded-xl border border-zinc-200 bg-zinc-50/80 px-4 py-3 text-sm text-zinc-900 outline-none ring-emerald-500/30 transition focus:border-emerald-500 focus:bg-white focus:ring-4 dark:border-zinc-700 dark:bg-zinc-950/50 dark:text-zinc-100 dark:focus:border-emerald-400"
                />
                @error('name')
                    <p class="mt-1 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label
                    for="member-email"
                    class="block text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                >
                    Email
                </label>
                <input
                    wire:model="email"
                    id="member-email"
                    type="email"
                    autocomplete="email"
                    required
                    class="mt-1.5 block w-full rounded-xl border border-zinc-200 bg-zinc-50/80 px-4 py-3 text-sm text-zinc-900 outline-none ring-emerald-500/30 transition focus:border-emerald-500 focus:bg-white focus:ring-4 dark:border-zinc-700 dark:bg-zinc-950/50 dark:text-zinc-100 dark:focus:border-emerald-400"
                />
                @error('email')
                    <p class="mt-1 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <button
                type="submit"
                class="inline-flex items-center rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-5 py-2.5 text-sm font-bold text-white shadow-md shadow-emerald-900/20 transition hover:from-emerald-500 hover:to-teal-500 dark:shadow-emerald-950/40"
                wire:loading.attr="disabled"
                wire:target="saveProfile"
            >
                <span wire:loading.remove wire:target="saveProfile">Save profile</span>
                <span wire:loading wire:target="saveProfile">Saving…</span>
            </button>
        </form>
    </section>

    <section
        class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80 sm:p-8"
    >
        <div class="flex items-center gap-2">
            <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Password</h2>
            <span class="text-xl" aria-hidden="true">🔒</span>
        </div>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Change it anytime — pick something only you’d volley with.</p>

        <form wire:submit="updatePassword" class="mt-6 space-y-5">
            <div>
                <label
                    for="current-password"
                    class="block text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                >
                    Current password
                </label>
                <input
                    wire:model="current_password"
                    id="current-password"
                    type="password"
                    autocomplete="current-password"
                    class="mt-1.5 block w-full rounded-xl border border-zinc-200 bg-zinc-50/80 px-4 py-3 text-sm text-zinc-900 outline-none ring-emerald-500/30 transition focus:border-emerald-500 focus:bg-white focus:ring-4 dark:border-zinc-700 dark:bg-zinc-950/50 dark:text-zinc-100 dark:focus:border-emerald-400"
                />
                @error('current_password')
                    <p class="mt-1 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label
                    for="new-password"
                    class="block text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                >
                    New password
                </label>
                <input
                    wire:model="new_password"
                    id="new-password"
                    type="password"
                    autocomplete="new-password"
                    class="mt-1.5 block w-full rounded-xl border border-zinc-200 bg-zinc-50/80 px-4 py-3 text-sm text-zinc-900 outline-none ring-emerald-500/30 transition focus:border-emerald-500 focus:bg-white focus:ring-4 dark:border-zinc-700 dark:bg-zinc-950/50 dark:text-zinc-100 dark:focus:border-emerald-400"
                />
                @error('new_password')
                    <p class="mt-1 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label
                    for="new-password-confirmation"
                    class="block text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                >
                    Confirm new password
                </label>
                <input
                    wire:model="new_password_confirmation"
                    id="new-password-confirmation"
                    type="password"
                    autocomplete="new-password"
                    class="mt-1.5 block w-full rounded-xl border border-zinc-200 bg-zinc-50/80 px-4 py-3 text-sm text-zinc-900 outline-none ring-emerald-500/30 transition focus:border-emerald-500 focus:bg-white focus:ring-4 dark:border-zinc-700 dark:bg-zinc-950/50 dark:text-zinc-100 dark:focus:border-emerald-400"
                />
            </div>
            <button
                type="submit"
                class="inline-flex items-center rounded-xl border-2 border-emerald-600 bg-transparent px-5 py-2.5 text-sm font-bold text-emerald-700 transition hover:bg-emerald-50 dark:border-emerald-500 dark:text-emerald-400 dark:hover:bg-emerald-950/40"
                wire:loading.attr="disabled"
                wire:target="updatePassword"
            >
                <span wire:loading.remove wire:target="updatePassword">Update password</span>
                <span wire:loading wire:target="updatePassword">Updating…</span>
            </button>
        </form>
    </section>
</div>
