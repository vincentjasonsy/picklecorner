@if (config('demo.quick_login_enabled'))
    @php
        $slots = [
            ['role' => 'player', 'label' => 'Player', 'hint' => 'Member app'],
            ['role' => 'open_play_host', 'label' => 'Open play host', 'hint' => 'Host listings'],
            ['role' => 'court_admin', 'label' => 'Court admin', 'hint' => 'Venue portal'],
            ['role' => 'desk', 'label' => 'Front desk', 'hint' => 'Counter'],
            ['role' => 'coach', 'label' => 'Coach', 'hint' => 'Coach tools'],
        ];
    @endphp
    <div
        class="mt-6 rounded-2xl border border-emerald-200/80 bg-gradient-to-br from-emerald-50/90 to-teal-50/40 p-4 dark:border-emerald-900/50 dark:from-emerald-950/40 dark:to-teal-950/20"
    >
        <p class="font-display text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">
            Quick login
        </p>
        <p class="mt-1 text-xs leading-relaxed text-emerald-900/85 dark:text-emerald-100/85">
            Jump in with seeded demo accounts (<span class="font-mono text-[11px]">php artisan migrate --seed</span>).
            Password for all is <span class="font-semibold">password</span> if you sign in manually.
        </p>
        <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($slots as $slot)
                <form method="POST" action="{{ route('demo.quick-login') }}" class="contents">
                    @csrf
                    <input type="hidden" name="role" value="{{ $slot['role'] }}" />
                    <button
                        type="submit"
                        class="flex w-full flex-col items-stretch rounded-xl border border-emerald-200/90 bg-white/90 px-3 py-2.5 text-left text-sm font-semibold text-emerald-950 shadow-sm transition hover:border-emerald-400 hover:bg-white dark:border-emerald-800 dark:bg-zinc-900/80 dark:text-emerald-100 dark:hover:border-emerald-600 dark:hover:bg-zinc-900"
                    >
                        <span>{{ $slot['label'] }}</span>
                        <span class="mt-0.5 text-[11px] font-normal text-emerald-700/80 dark:text-emerald-300/80">
                            {{ $slot['hint'] }}
                        </span>
                    </button>
                </form>
            @endforeach
        </div>
    </div>
@endif
