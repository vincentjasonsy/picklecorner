<div class="space-y-10">
    <x-member.guide title="Why this screen exists">
        <p>
            Your name shows up on bookings and emails. Home city (if you pick one) nudges
            <strong class="font-semibold text-sky-950 dark:text-white">Find a court</strong>
            toward places you actually play.
        </p>
    </x-member.guide>

    <div>
        <h1 class="font-display text-2xl font-extrabold tracking-tight text-zinc-900 dark:text-white">Profile &amp; settings</h1>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
            Tweak what we call you and how we reach you — nothing fancy.
        </p>
    </div>

    <section
        class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80 sm:p-8"
    >
        <div class="flex items-center gap-2">
            <x-app-icon name="user-circle" class="size-6 text-emerald-600 dark:text-emerald-400" />
            <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">The basics</h2>
        </div>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            Name, email, and optional home base for quicker browsing.
        </p>

        <form wire:submit="saveProfile" class="mt-6 space-y-5">
            <div>
                <label
                    for="member-name"
                    class="block text-sm font-medium text-zinc-600 dark:text-zinc-300"
                >
                    Name
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
                    class="block text-sm font-medium text-zinc-600 dark:text-zinc-300"
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
            @if ($homeCityOptions->isNotEmpty())
                <div>
                    <label
                        for="member-home-city"
                        class="block text-sm font-medium text-zinc-600 dark:text-zinc-300"
                    >
                        Home base (optional)
                    </label>
                    <select
                        wire:model="home_city"
                        id="member-home-city"
                        class="mt-1.5 block w-full rounded-xl border border-zinc-200 bg-zinc-50/80 px-4 py-3 text-sm text-zinc-900 outline-none ring-emerald-500/30 transition focus:border-emerald-500 focus:bg-white focus:ring-4 dark:border-zinc-700 dark:bg-zinc-950/50 dark:text-zinc-100 dark:focus:border-emerald-400"
                    >
                        <option value="">No preference</option>
                        @foreach ($homeCityOptions as $cityName)
                            <option value="{{ $cityName }}">{{ $cityName }}</option>
                        @endforeach
                    </select>
                    @error('home_city')
                        <p class="mt-1 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            @endif
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
            <x-app-icon name="squares-2x2" class="size-6 text-emerald-600 dark:text-emerald-400" />
            <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">GameQ — your rivals &amp; partners</h2>
        </div>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            When you save a GameQ session, we match <span class="font-medium text-zinc-700 dark:text-zinc-300">this name</span> to the roster (case doesn’t matter) and tally who you’ve played.
        </p>

        @if ($gameqSessionsTotal === 0)
            <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">
                You don’t have any saved GameQ sessions yet. Host in
                <a href="{{ route('account.open-play') }}" wire:navigate class="font-semibold text-emerald-700 underline decoration-emerald-600/30 underline-offset-2 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300">GameQ</a>
                and save a session to build history here.
            </p>
        @elseif ($gameqProfile['sessions_matched'] === 0)
            <div class="mt-4 rounded-xl border border-amber-200/90 bg-amber-50/90 px-4 py-3 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/35 dark:text-amber-100">
                None of your {{ $gameqSessionsTotal }} saved session{{ $gameqSessionsTotal === 1 ? '' : 's' }} include a player whose name matches your profile name. Use the same spelling as your roster entry (or update your display name above) so we can attribute matches to you.
            </div>
        @elseif ($gameqProfile['matches_counted'] === 0 && count($gameqProfile['opponents']) === 0)
            <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">
                You’re on the roster in {{ $gameqProfile['sessions_matched'] }} saved session{{ $gameqProfile['sessions_matched'] === 1 ? '' : 's' }}, but there are no finished matches in the log yet.
            </p>
        @else
            <div class="mt-6 space-y-6">
                @if (count($gameqProfile['opponents']) > 0)
                    <div>
                        <p class="text-sm font-semibold text-zinc-600 dark:text-zinc-300">Head-to-head</p>
                        <ul class="mt-3 divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($gameqProfile['opponents'] as $row)
                                <li class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 py-3 first:pt-0">
                                    <span class="min-w-0 font-medium text-zinc-900 dark:text-zinc-100">{{ $row['displayName'] }}</span>
                                    <span class="shrink-0 text-right">
                                        <span class="font-mono text-sm font-bold tabular-nums text-emerald-700 dark:text-emerald-400">
                                            {{ $row['wins'] }}W · {{ $row['losses'] }}L
                                        </span>
                                        @if (($row['ties'] ?? 0) > 0)
                                            <span class="ml-2 text-[11px] text-zinc-400 dark:text-zinc-500">{{ $row['ties'] }} tie{{ $row['ties'] === 1 ? '' : 's' }}</span>
                                        @endif
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (count($gameqProfile['partners']) > 0)
                    <div>
                        <p class="text-sm font-semibold text-zinc-600 dark:text-zinc-300">Doubles partners</p>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-500">Same-side games — not wins/losses against them.</p>
                        <ul class="mt-3 divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($gameqProfile['partners'] as $row)
                                <li class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 py-3 first:pt-0">
                                    <span class="min-w-0 font-medium text-zinc-900 dark:text-zinc-100">{{ $row['displayName'] }}</span>
                                    <span class="shrink-0 font-mono text-sm tabular-nums text-zinc-600 dark:text-zinc-400">{{ $row['games'] }} {{ $row['games'] === 1 ? 'game' : 'games' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <p class="text-[11px] leading-relaxed text-zinc-400 dark:text-zinc-500">
                    Based on {{ $gameqProfile['matches_counted'] }} finished match{{ $gameqProfile['matches_counted'] === 1 ? '' : 'es' }} across {{ $gameqProfile['sessions_matched'] }} session{{ $gameqProfile['sessions_matched'] === 1 ? '' : 's' }}. Duplicate names on different people are merged by name.
                </p>
            </div>
        @endif
    </section>

    <section
        class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80 sm:p-8"
    >
        <div class="flex items-center gap-2">
            <x-app-icon name="lock-closed" class="size-6 text-emerald-600 dark:text-emerald-400" />
            <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Password</h2>
        </div>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Change it whenever — you know the drill.</p>

        <form wire:submit="updatePassword" class="mt-6 space-y-5">
            <div>
                <label
                    for="current-password"
                    class="block text-sm font-medium text-zinc-600 dark:text-zinc-300"
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
                    class="block text-sm font-medium text-zinc-600 dark:text-zinc-300"
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
                    class="block text-sm font-medium text-zinc-600 dark:text-zinc-300"
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
