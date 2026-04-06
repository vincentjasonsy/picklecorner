@php
    $players = collect($p['players'] ?? []);
    $byId = $players->keyBy('id');
    $label = function (string $id) use ($byId): string {
        $row = $byId->get($id);

        return is_array($row) ? (string) ($row['name'] ?? '?') : '?';
    };
    $sideLabels = function (array $ids) use ($label): string {
        return collect($ids)->map(fn ($id) => $label((string) $id))->implode(' · ');
    };
    $mode = $p['mode'] ?? 'singles';
    $courts = $p['courts'] ?? [];
    $queue = $p['queue'] ?? [];
    $timeLimit = (int) ($p['timeLimitMinutes'] ?? 0);
@endphp

<div wire:poll.5s class="mx-auto max-w-2xl px-4 py-10 sm:px-6 lg:px-8">
    <header class="mb-10">
        <p class="text-sm text-sky-700 dark:text-sky-400">
            Live view · refreshes automatically
        </p>
        <h1 class="mt-2 flex flex-wrap items-center gap-2 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100 sm:text-3xl">
            <x-gameq-mark />
        </h1>
        <p class="mt-2 text-slate-600 dark:text-slate-400">
            {{ $mode === 'doubles' ? 'Doubles' : 'Singles' }}
            @if ($timeLimit > 0)
                · {{ $timeLimit }} min timer (from host)
            @endif
        </p>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-500">
            Updated {{ $openPlayShare->updated_at->timezone(config('app.timezone'))->diffForHumans() }}
        </p>
    </header>

    <section class="mb-10 space-y-4">
        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">
            Courts
        </h2>
        @forelse ($courts as $i => $court)
            @if (is_array($court))
                <div class="rounded-2xl border border-sky-100 bg-sky-50/50 p-5 dark:border-sky-900/30 dark:bg-sky-950/15">
                    <p class="text-sm font-medium text-slate-600 dark:text-slate-400">
                        Court {{ $i + 1 }}
                    </p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2 sm:gap-6">
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Side A</p>
                            <p class="mt-0.5 text-slate-900 dark:text-slate-100">
                                {{ $sideLabels($court['sideA'] ?? []) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Side B</p>
                            <p class="mt-0.5 text-slate-900 dark:text-slate-100">
                                {{ $sideLabels($court['sideB'] ?? []) }}
                            </p>
                        </div>
                    </div>
                </div>
            @else
                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 px-4 py-3 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900/20 dark:text-slate-400">
                    Court {{ $i + 1 }} — open
                </div>
            @endif
        @empty
            <p class="text-sm text-slate-500 dark:text-slate-400">No court info yet.</p>
        @endforelse
    </section>

    <section class="mb-10">
        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">
            Waiting
        </h2>
        <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-slate-800 dark:text-slate-200">
            @forelse ($queue as $qid)
                <li>{{ $label((string) $qid) }}</li>
            @empty
                <li class="list-none pl-0 text-slate-500 dark:text-slate-400">No one in line.</li>
            @endforelse
        </ol>
    </section>

    <section>
        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">
            Playing today
        </h2>
        <ul class="mt-3 divide-y divide-slate-100 dark:divide-slate-800">
            @forelse ($players->filter(fn ($x) => is_array($x) && empty($x['disabled'])) as $player)
                <li class="flex flex-wrap items-baseline justify-between gap-2 py-3 text-sm">
                    <span class="font-medium text-slate-900 dark:text-slate-100">{{ $player['name'] ?? '—' }}</span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">
                        Lvl {{ (int) ($player['level'] ?? 0) }}
                        @if (! empty($player['teamId']))
                            · Team {{ $player['teamId'] }}
                        @endif
                        · {{ (int) ($player['wins'] ?? 0) }}W {{ (int) ($player['losses'] ?? 0) }}L
                    </span>
                </li>
            @empty
                <li class="py-3 text-sm text-slate-500 dark:text-slate-400">No active players listed.</li>
            @endforelse
        </ul>
    </section>
</div>
