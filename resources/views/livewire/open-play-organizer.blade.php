@php
    use App\GameQ\Engine;
    use App\Models\OpenPlaySession;
    use Illuminate\Support\Carbon;

    $eq = new Engine($state);
    $uiPhase = $state['uiPhase'] ?? 'list';
    $setupStep = (int) ($state['setupStep'] ?? 1);
    $historySaveDisabled = is_array($historyQuota ?? null) && (int) ($historyQuota['remaining'] ?? 0) <= 0;
@endphp

<div
    class="min-h-0"
    @if ($uiPhase === 'session' && (int) ($state['timeLimitMinutes'] ?? 0) > 0)
        wire:poll.1s="refreshTimers"
    @endif
>
    {{-- ========== LIST: history table + create ========== --}}
    @if ($uiPhase === 'list')
        <div class="space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="font-display text-lg font-bold tracking-tight text-zinc-900 dark:text-white">
                        Your sessions
                    </h2>
                    <p class="mt-1 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                        Open a saved session or start a new queue.
                    </p>
                </div>
                <button
                    type="button"
                    class="shrink-0 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-md shadow-emerald-600/25 transition hover:bg-emerald-500 active:scale-[0.98] dark:bg-emerald-600 dark:shadow-emerald-900/30 dark:hover:bg-emerald-500"
                    wire:click="startOpenPlayWizard"
                >
                    Start GameQ
                </button>
            </div>

            <div
                class="overflow-hidden rounded-3xl border border-zinc-200/90 bg-white shadow-md dark:border-zinc-800 dark:bg-zinc-900/80"
            >
                @if ($historyQuota)
                    <p class="border-b border-zinc-100 px-4 py-2 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-500">
                        <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ (int) ($historyQuota['used'] ?? 0) }}/{{ (int) ($historyQuota['limit'] ?? 0) }}</span>
                        uses this month (saved sessions) · resets
                        {{ isset($historyQuota['resets_at']) ? Carbon::parse($historyQuota['resets_at'])->timezone(config('app.timezone'))->format('M j, Y') : '' }}
                    </p>
                @endif
                @if ($historySaveDisabled)
                    <p class="px-4 py-2 text-sm text-amber-800 dark:text-amber-200/90">
                        Monthly limit reached (5 uses) — open an existing save or wait until next month.
                    </p>
                @endif
                @if ($historyError !== '')
                    <p class="px-4 py-2 text-sm text-red-600 dark:text-red-400">{{ $historyError }}</p>
                @endif

                <div class="hidden overflow-x-auto md:block">
                    <table class="min-w-full text-left text-sm">
                        <thead class="border-b border-zinc-200 bg-zinc-50/90 dark:border-zinc-800 dark:bg-zinc-950/80">
                            <tr class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                <th class="px-4 py-3">Session</th>
                                <th class="px-4 py-3">Hosted</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse ($historySessions as $row)
                                <tr class="text-zinc-800 dark:text-zinc-200" wire:key="history-desktop-{{ $row['id'] }}">
                                    <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $row['title'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                        {{ Carbon::parse($row['created_at'] ?? $row['updated_at'])->timezone(config('app.timezone'))->format('M j, Y g:i a') }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        <button
                                            type="button"
                                            class="touch-manipulation mr-2 rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-500 active:scale-[0.98]"
                                            wire:click="loadHistorySession({{ (int) $row['id'] }})"
                                        >
                                            Open
                                        </button>
                                        <button
                                            type="button"
                                            class="touch-manipulation rounded-xl border border-zinc-200 px-3 py-1.5 text-xs font-semibold text-zinc-600 hover:bg-zinc-50 active:scale-[0.98] dark:border-zinc-600 dark:text-zinc-400 dark:hover:bg-zinc-800/50"
                                            wire:confirm="Remove this saved session?"
                                            wire:click="deleteHistorySession({{ (int) $row['id'] }})"
                                        >
                                            Remove
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                @if ($historyError === '')
                                    <tr>
                                        <td colspan="3" class="px-4 py-14 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                            <p class="font-medium text-zinc-700 dark:text-zinc-300">No sessions yet</p>
                                            <p class="mt-2">
                                                <button
                                                    type="button"
                                                    class="font-semibold text-emerald-700 hover:underline dark:text-emerald-400"
                                                    wire:click="startOpenPlayWizard"
                                                >
                                                    Start your first GameQ
                                                </button>
                                            </p>
                                        </td>
                                    </tr>
                                @endif
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="divide-y divide-zinc-100 md:hidden dark:divide-zinc-800">
                    @forelse ($historySessions as $row)
                        <div class="px-4 py-4" wire:key="history-mobile-{{ $row['id'] }}">
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $row['title'] }}</p>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ Carbon::parse($row['created_at'] ?? $row['updated_at'])->timezone(config('app.timezone'))->format('M j, Y g:i a') }}
                            </p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    class="touch-manipulation min-h-11 flex-1 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white active:scale-[0.98] sm:flex-none"
                                    wire:click="loadHistorySession({{ (int) $row['id'] }})"
                                >
                                    Open
                                </button>
                                <button
                                    type="button"
                                    class="touch-manipulation min-h-11 flex-1 rounded-xl border border-zinc-200 px-4 py-2.5 text-sm font-semibold text-zinc-700 active:scale-[0.98] dark:border-zinc-600 dark:text-zinc-300 sm:flex-none"
                                    wire:confirm="Remove this saved session?"
                                    wire:click="deleteHistorySession({{ (int) $row['id'] }})"
                                >
                                    Remove
                                </button>
                            </div>
                        </div>
                    @empty
                        @if ($historyError === '')
                            <div class="px-4 py-14 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                <p class="font-medium text-zinc-700 dark:text-zinc-300">No sessions yet</p>
                                <p class="mt-2">
                                    <button
                                        type="button"
                                        class="font-semibold text-emerald-700 hover:underline dark:text-emerald-400"
                                        wire:click="startOpenPlayWizard"
                                    >
                                        Start your first GameQ
                                    </button>
                                </p>
                            </div>
                        @endif
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    {{-- ========== SETUP WIZARD ========== --}}
    @if ($uiPhase === 'setup')
        <div class="mx-auto max-w-lg space-y-8 pb-8">
            <div class="flex justify-end">
                <span class="text-xs font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                    Step {{ $setupStep }} / 3
                </span>
            </div>

            <div class="flex justify-center gap-2">
                @foreach ([1, 2, 3] as $n)
                    <span
                        class="h-2 w-8 rounded-full transition-colors {{ $setupStep >= $n ? 'bg-emerald-500' : 'bg-zinc-200 dark:bg-zinc-700' }}"
                    ></span>
                @endforeach
            </div>

            <div class="rounded-3xl border border-zinc-200/90 bg-white p-6 shadow-md dark:border-zinc-800 dark:bg-zinc-900/80">
                @if ($setupStep === 1)
                    <div class="space-y-5">
                        <h2 class="font-display text-lg font-extrabold text-zinc-900 dark:text-white">Session rules</h2>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Format
                            <select wire:model.live="state.mode" class="mt-1.5 w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100">
                                <option value="singles">Singles</option>
                                <option value="doubles">Doubles</option>
                            </select>
                        </label>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Who plays next
                            <select wire:model.live="state.shuffleMethod" class="mt-1.5 w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100">
                                <option value="random">Random</option>
                                <option value="wins">Fewest wins first</option>
                                <option value="levels">By skill level</option>
                                <option value="teams">Fixed pairs (team on each player)</option>
                            </select>
                        </label>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                Courts
                                <input
                                    type="number"
                                    min="1"
                                    max="8"
                                    wire:model.live="state.courtsCount"
                                    wire:change="courtsCountChanged"
                                    class="mt-1.5 w-full rounded-xl border border-zinc-200 px-3 py-2.5 text-sm dark:border-zinc-600 dark:bg-zinc-950"
                                />
                            </label>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                Timer (min, 0 = off)
                                <input
                                    type="number"
                                    min="0"
                                    max="120"
                                    wire:model.live="state.timeLimitMinutes"
                                    class="mt-1.5 w-full rounded-xl border border-zinc-200 px-3 py-2.5 text-sm dark:border-zinc-600 dark:bg-zinc-950"
                                />
                            </label>
                        </div>
                        @if (($state['mode'] ?? '') === 'doubles' && ($state['shuffleMethod'] ?? '') === 'teams')
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                Use the same team name for partners in the next step.
                            </p>
                        @endif
                        @if (count($state['players'] ?? []) < 2)
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                Add at least two players in step 2 to see suggested courts and rotation time.
                            </p>
                        @endif
                        @if (count($state['players'] ?? []) >= 2)
                            <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/50 p-4 dark:border-emerald-900/40 dark:bg-emerald-950/20">
                                <div class="space-y-2 text-xs text-emerald-900/95 dark:text-emerald-100/90">
                                    <p class="font-bold uppercase tracking-wide text-emerald-800 dark:text-emerald-200/90">Suggestions</p>
                                    <p class="leading-relaxed">
                                        <span class="font-semibold text-emerald-950 dark:text-emerald-50">Courts:</span>
                                        {{ $eq->setupCourtCountHint() }}
                                    </p>
                                    <p class="leading-relaxed">
                                        <span class="font-semibold text-emerald-950 dark:text-emerald-50">Time (rough):</span>
                                        @php $estRot = $eq->setupEstimatedRotationMinutes(); @endphp
                                        ~{{ $estRot ?? '—' }}
                                        min per queue wave, using
                                        @if ((int) ($state['timeLimitMinutes'] ?? 0) > 0)
                                            {{ (int) $state['timeLimitMinutes'] }} min (match timer)
                                        @else
                                            {{ $eq->setupMinutesPerMatchFallback() }} min assumed per match
                                        @endif
                                        .
                                    </p>
                                    <p class="leading-relaxed">
                                        <span class="font-semibold text-emerald-950 dark:text-emerald-50">Even counts:</span>
                                        {{ $eq->setupPlayerParityHint() }}
                                    </p>
                                    <p class="text-[11px] leading-relaxed text-emerald-800/85 dark:text-emerald-300/85">
                                        Heuristic only — real time depends on warm-ups, walkovers, and between games.
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                @if ($setupStep === 2)
                    <div class="space-y-5">
                        <h2 class="font-display text-lg font-extrabold text-zinc-900 dark:text-white">Players</h2>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Add who’s in today. You can skip and add later from the host screen.</p>
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">
                            {{ count($state['players'] ?? []) }} / {{ OpenPlaySession::MAX_PLAYERS_PER_SESSION }} players max
                        </p>
                        @if (count($state['players'] ?? []) >= 2)
                            <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/50 p-4 dark:border-emerald-900/40 dark:bg-emerald-950/20">
                                <div class="space-y-2 text-xs text-emerald-900/95 dark:text-emerald-100/90">
                                    <p class="font-bold uppercase tracking-wide text-emerald-800 dark:text-emerald-200/90">Suggestions</p>
                                    <p class="leading-relaxed">
                                        <span class="font-semibold text-emerald-950 dark:text-emerald-50">Courts:</span>
                                        {{ $eq->setupCourtCountHint() }}
                                    </p>
                                    <p class="leading-relaxed">
                                        <span class="font-semibold text-emerald-950 dark:text-emerald-50">Time (rough):</span>
                                        @php $estRot2 = $eq->setupEstimatedRotationMinutes(); @endphp
                                        ~{{ $estRot2 ?? '—' }}
                                        min per queue wave, using
                                        @if ((int) ($state['timeLimitMinutes'] ?? 0) > 0)
                                            {{ (int) $state['timeLimitMinutes'] }} min (match timer)
                                        @else
                                            {{ $eq->setupMinutesPerMatchFallback() }} min assumed per match
                                        @endif
                                        .
                                    </p>
                                    <p class="leading-relaxed">
                                        <span class="font-semibold text-emerald-950 dark:text-emerald-50">Even counts:</span>
                                        {{ $eq->setupPlayerParityHint() }}
                                    </p>
                                    <p class="text-[11px] leading-relaxed text-emerald-800/85 dark:text-emerald-300/85">
                                        Heuristic only — real time depends on warm-ups, walkovers, and between games.
                                    </p>
                                </div>
                            </div>
                        @endif
                        <div class="flex flex-wrap items-end gap-3">
                            <label class="min-w-[10rem] grow text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                Name
                                <input
                                    type="text"
                                    wire:model.live="state.newName"
                                    placeholder="Name"
                                    class="mt-1.5 w-full rounded-xl border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
                                />
                            </label>
                            <label class="w-20 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                Lvl
                                <input
                                    type="number"
                                    min="1"
                                    max="10"
                                    wire:model.live="state.newLevel"
                                    class="mt-1.5 w-full rounded-xl border border-zinc-200 px-2 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
                                />
                            </label>
                            <label class="min-w-[6rem] grow text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                Team
                                <input
                                    type="text"
                                    wire:model.live="state.newTeamId"
                                    placeholder="Optional"
                                    class="mt-1.5 w-full rounded-xl border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
                                />
                            </label>
                            <button
                                type="button"
                                class="rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-50"
                                wire:click="addPlayer"
                                @disabled(count($state['players'] ?? []) >= OpenPlaySession::MAX_PLAYERS_PER_SESSION)
                            >
                                Add
                            </button>
                        </div>
                        <div class="rounded-2xl border border-zinc-200/90 bg-zinc-50/80 p-4 dark:border-zinc-700 dark:bg-zinc-950/40">
                            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Add a list</p>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                One name per line. Numbered lines like <span class="font-mono">1. Sam</span> or <span class="font-mono">2) Alex</span> are cleaned automatically. Duplicates in the box are merged before adding.
                            </p>
                            <label class="mt-3 block text-xs font-medium text-zinc-600 dark:text-zinc-400">
                                Paste names
                                    <textarea
                                        wire:model.live="state.bulkPlayerList"
                                        rows="5"
                                        placeholder="Sam&#10;2. Jordan&#10;3) Casey"
                                        class="mt-1.5 w-full resize-y rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 placeholder:text-zinc-400 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                                    ></textarea>
                            </label>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    class="rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700"
                                    wire:click="cleanupBulkPlayerList"
                                >
                                    Clean up list
                                </button>
                                <button
                                    type="button"
                                    class="rounded-xl bg-emerald-600 px-3 py-2 text-sm font-bold text-white hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-50"
                                    wire:click="addPlayersFromBulk"
                                    @disabled(count($state['players'] ?? []) >= OpenPlaySession::MAX_PLAYERS_PER_SESSION)
                                >
                                    Add from list
                                </button>
                            </div>
                            @if (! empty($state['bulkAddFeedback'] ?? ''))
                                <p class="mt-2 text-xs text-zinc-600 dark:text-zinc-400">{{ $state['bulkAddFeedback'] }}</p>
                            @endif
                        </div>
                        @if (count($state['players'] ?? []) > 0)
                            <ul class="max-h-48 space-y-1 overflow-y-auto text-sm">
                                @foreach ($state['players'] as $p)
                                    <li class="flex items-center justify-between rounded-2xl bg-zinc-50 px-3 py-2 dark:bg-zinc-950/60" wire:key="setup-pl-{{ $p['id'] }}">
                                        <span>{{ $p['name'] }}</span>
                                        <button type="button" class="text-xs text-zinc-500 hover:text-red-600" wire:click="removePlayer('{{ $p['id'] }}')">Remove</button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @if (count($state['players'] ?? []) === 0)
                            <p class="text-sm text-zinc-400">No players added yet.</p>
                        @endif
                    </div>
                @endif

                @if ($setupStep === 3)
                    <div class="space-y-5">
                        <h2 class="font-display text-lg font-extrabold text-zinc-900 dark:text-white">Ready</h2>
                        @php $sug = $eq->setupSuggestedCourtsCount(); $estRot3 = $eq->setupEstimatedRotationMinutes(); @endphp
                        <ul class="space-y-2 text-sm text-zinc-700 dark:text-zinc-300">
                            <li><span class="text-zinc-500">Format:</span> <span class="font-medium capitalize">{{ $state['mode'] ?? 'singles' }}</span></li>
                            <li><span class="text-zinc-500">Pairing:</span> <span class="font-medium">{{ $eq->shuffleMethodLabel() }}</span></li>
                            <li><span class="text-zinc-500">Courts:</span> <span class="font-medium">{{ (int) ($state['courtsCount'] ?? 1) }}</span></li>
                            <li><span class="text-zinc-500">Players:</span> <span class="font-medium">{{ count($state['players'] ?? []) }}</span></li>
                            @if (count($state['players'] ?? []) >= 2)
                                <li>
                                    <span class="text-zinc-500">Suggested courts:</span>
                                    <span class="font-medium">{{ $sug['ideal'] ?? '—' }}</span>
                                    <span class="text-zinc-500">(typical for this roster)</span>
                                </li>
                                <li>
                                    <span class="text-zinc-500">Rough time / wave:</span>
                                    <span class="font-medium">~{{ $estRot3 ?? '—' }} min</span>
                                    <span class="text-zinc-500">with</span>
                                    <span class="font-medium">
                                        @if ((int) ($state['timeLimitMinutes'] ?? 0) > 0)
                                            {{ (int) $state['timeLimitMinutes'] }} min timer
                                        @else
                                            15 min / match (assumed)
                                        @endif
                                    </span>
                                </li>
                                <li class="text-zinc-600 dark:text-zinc-400">{{ $eq->setupPlayerParityHint() }}</li>
                            @endif
                        </ul>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            Next: roster stays on the left while you host; open <span class="font-medium text-zinc-700 dark:text-zinc-300">Standings &amp; log</span> anytime for the full leaderboard and match history.
                        </p>
                    </div>
                @endif

                <div class="mt-8 flex justify-between gap-3 border-t border-zinc-100 pt-6 dark:border-zinc-800">
                    <button
                        type="button"
                        class="rounded-2xl border border-zinc-200 px-4 py-2.5 text-sm font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800"
                        wire:click="setupGoBack"
                    >
                        {{ $setupStep === 1 ? 'All sessions' : 'Back' }}
                    </button>
                    @if ($setupStep < 3)
                        <button
                            type="button"
                            class="rounded-2xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-emerald-500"
                            wire:click="setupGoNext"
                        >
                            Next
                        </button>
                    @endif
                    @if ($setupStep === 3)
                        <button
                            type="button"
                            class="rounded-2xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-emerald-500"
                            wire:click="finishSetup"
                        >
                            Start hosting
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- ========== MINIMAL HOST VIEW ========== --}}
    @if ($uiPhase === 'session')
        <div class="space-y-4 pb-[max(1rem,env(safe-area-inset-bottom))] sm:space-y-5">
            <header class="flex flex-wrap items-center justify-between gap-3 pb-1">
                <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
                    <button
                        type="button"
                        class="touch-manipulation shrink-0 rounded-2xl border border-zinc-200/90 bg-white px-3 py-2.5 text-sm font-semibold text-zinc-700 shadow-sm hover:bg-zinc-50 active:scale-[0.98] dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
                        wire:click="goToSessionList"
                    >
                        ← Sessions
                    </button>
                    <button
                        type="button"
                        class="touch-manipulation shrink-0 rounded-2xl border border-red-200/90 bg-white px-3 py-2.5 text-sm font-semibold text-red-800 shadow-sm hover:bg-red-50 active:scale-[0.98] dark:border-red-900/50 dark:bg-zinc-900 dark:text-red-300 dark:hover:bg-red-950/40"
                        wire:confirm="End this hosting session? Your local queue and scores will be cleared."
                        wire:click="endHostingSession"
                    >
                        End session
                    </button>
                    <h1 class="font-display min-w-0 truncate text-lg font-extrabold text-zinc-900 dark:text-white">Hosting</h1>
                </div>
                <div class="flex w-full shrink-0 items-center gap-2 sm:w-auto">
                    <button
                        type="button"
                        class="touch-manipulation min-h-11 flex-1 rounded-2xl border border-zinc-200/90 bg-emerald-600/10 px-4 py-2.5 text-sm font-bold text-emerald-800 hover:bg-emerald-600/15 active:scale-[0.98] dark:border-emerald-800/50 dark:bg-emerald-950/40 dark:text-emerald-200 dark:hover:bg-emerald-950/60 sm:flex-none"
                        wire:click="$set('peopleModalOpen', true)"
                    >
                        Standings &amp; log
                    </button>
                </div>
            </header>

            <div class="flex flex-col gap-6 xl:flex-row xl:items-start xl:gap-6 2xl:gap-8">
                {{-- Left: roster --}}
                <aside
                    class="order-2 min-w-0 xl:order-1 xl:w-[min(100%,24rem)] xl:max-w-[40%] xl:shrink-0 xl:sticky xl:top-4 xl:z-10 xl:max-h-[calc(100dvh-6rem)] xl:overflow-y-auto"
                >
                    <div class="rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900/60">
                        <h2 class="font-display text-base font-extrabold text-zinc-900 dark:text-white">Roster</h2>
                        <p class="mt-1 text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">
                            Add players and toggles here; courts and queue stay on the right.
                        </p>
                        <div class="mt-4 space-y-6">
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                {{ count($state['players'] ?? []) }} / {{ OpenPlaySession::MAX_PLAYERS_PER_SESSION }} players max
                            </p>
                            <div class="flex flex-wrap items-end gap-3">
                                <label class="min-w-[10rem] grow text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    Name
                                    <input
                                        type="text"
                                        wire:model.live="state.newName"
                                        class="mt-1 w-full rounded-xl border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
                                        placeholder="Name"
                                    />
                                </label>
                                <label class="w-20 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    Lvl
                                    <input
                                        type="number"
                                        min="1"
                                        max="10"
                                        wire:model.live="state.newLevel"
                                        class="mt-1 w-full rounded-xl border border-zinc-200 px-2 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
                                    />
                                </label>
                                <label class="min-w-[6rem] grow text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    Team
                                    <input
                                        type="text"
                                        wire:model.live="state.newTeamId"
                                        class="mt-1 w-full rounded-xl border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
                                        placeholder="Optional"
                                    />
                                </label>
                                <button
                                    type="button"
                                    class="rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-50"
                                    wire:click="addPlayer"
                                    @disabled(count($state['players'] ?? []) >= OpenPlaySession::MAX_PLAYERS_PER_SESSION)
                                >
                                    Add
                                </button>
                            </div>
                            <div class="rounded-2xl border border-zinc-200/90 bg-zinc-50/80 p-4 dark:border-zinc-700 dark:bg-zinc-950/40">
                                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Add a list</p>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                    One name per line. Numbered lines are cleaned; duplicates in the box are merged. New players use the level and team fields above.
                                </p>
                                <label class="mt-3 block text-xs font-medium text-zinc-600 dark:text-zinc-400">
                                    Paste names
                                    <textarea
                                        wire:model.live="state.bulkPlayerList"
                                        rows="5"
                                        placeholder="Sam&#10;2. Jordan&#10;3) Casey"
                                        class="mt-1.5 w-full resize-y rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 placeholder:text-zinc-400 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                                    ></textarea>
                                </label>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        class="rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700"
                                        wire:click="cleanupBulkPlayerList"
                                    >
                                        Clean up list
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-xl bg-emerald-600 px-3 py-2 text-sm font-bold text-white hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-50"
                                        wire:click="addPlayersFromBulk"
                                        @disabled(count($state['players'] ?? []) >= OpenPlaySession::MAX_PLAYERS_PER_SESSION)
                                    >
                                        Add from list
                                    </button>
                                </div>
                                @if (! empty($state['bulkAddFeedback'] ?? ''))
                                    <p class="mt-2 text-xs text-zinc-600 dark:text-zinc-400">{{ $state['bulkAddFeedback'] }}</p>
                                @endif
                            </div>
                            <div class="overflow-x-auto rounded-2xl border border-zinc-200 dark:border-zinc-700">
                                <table class="w-full min-w-[40rem] text-left text-sm">
                                    <thead class="border-b border-zinc-200 bg-zinc-50 text-xs font-bold uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-400">
                                        <tr>
                                            <th class="px-3 py-2">Name</th>
                                            <th class="px-3 py-2">Lvl</th>
                                            <th class="px-3 py-2">Team</th>
                                            <th class="px-3 py-2">W–L</th>
                                            <th class="min-w-[6.5rem] px-3 py-2 leading-snug" title="Same as Take a break in the queue. Skip Fill courts until cleared; can still finish a game already on court.">Take a break</th>
                                            <th class="px-3 py-2" title="Off roster — removed from courts and queue">Active</th>
                                            <th class="px-3 py-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                        @foreach ($state['players'] ?? [] as $pi => $p)
                                            <tr
                                                class="{{ ! empty($p['disabled']) ? 'opacity-50' : (! empty($p['skipShuffle']) ? 'bg-amber-50/50 dark:bg-amber-950/15' : '') }}"
                                                wire:key="roster-{{ $p['id'] }}"
                                            >
                                                <td class="px-3 py-2">
                                                    <input
                                                        type="text"
                                                        wire:model.live="state.players.{{ $pi }}.name"
                                                        class="w-full min-w-[6rem] rounded border border-transparent bg-transparent py-0.5 text-sm focus:border-emerald-500 dark:text-zinc-100"
                                                    />
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input
                                                        type="number"
                                                        min="1"
                                                        max="10"
                                                        wire:model.live="state.players.{{ $pi }}.level"
                                                        class="w-12 rounded border border-zinc-200 px-1 py-0.5 dark:border-zinc-600 dark:bg-zinc-950"
                                                    />
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input
                                                        type="text"
                                                        wire:model.live="state.players.{{ $pi }}.teamId"
                                                        class="w-full max-w-[6rem] rounded border border-zinc-200 px-1 py-0.5 text-xs dark:border-zinc-600 dark:bg-zinc-950"
                                                        placeholder="—"
                                                    />
                                                </td>
                                                <td class="px-3 py-2 tabular-nums text-zinc-600 dark:text-zinc-400">
                                                    {{ (int) ($p['wins'] ?? 0) }}–{{ (int) ($p['losses'] ?? 0) }}
                                                </td>
                                                <td class="px-3 py-2">
                                                    <label class="inline-flex cursor-pointer items-center gap-1">
                                                        <input
                                                            type="checkbox"
                                                            class="h-5 w-5 rounded border-zinc-300 text-amber-600 touch-manipulation"
                                                            @checked(! empty($p['skipShuffle']))
                                                            @disabled(! empty($p['disabled']))
                                                            wire:click.prevent="toggleSkipShuffle('{{ $p['id'] }}')"
                                                            aria-label="Take a break: {{ $p['name'] }}"
                                                        />
                                                    </label>
                                                </td>
                                                <td class="px-3 py-2">
                                                    <label class="inline-flex cursor-pointer items-center gap-1">
                                                        <input
                                                            type="checkbox"
                                                            class="h-5 w-5 rounded border-zinc-300 text-emerald-600 touch-manipulation"
                                                            @checked(empty($p['disabled']))
                                                            wire:click.prevent="toggleDisabled('{{ $p['id'] }}')"
                                                            aria-label="Active roster {{ $p['name'] }}"
                                                        />
                                                    </label>
                                                </td>
                                                <td class="px-3 py-2 text-right">
                                                    <button type="button" class="touch-manipulation text-xs text-zinc-500 hover:text-red-600" wire:click="removePlayer('{{ $p['id'] }}')">Remove</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <button
                                    type="button"
                                    class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-500 active:scale-[0.99] dark:shadow-emerald-950/30"
                                    wire:click="saveRoster"
                                >
                                    <span wire:loading.remove wire:target="saveRoster">Save roster</span>
                                    <span wire:loading wire:target="saveRoster">Saving…</span>
                                </button>
                            </div>
                            @if (count($state['players'] ?? []) === 0)
                                <p class="text-sm text-zinc-500">No players yet.</p>
                            @endif
                        </div>
                    </div>
                </aside>

                {{-- Right: games & queue --}}
                <div class="order-1 min-w-0 flex-1 space-y-4 sm:space-y-5 xl:order-2">
                    <div class="flex flex-wrap gap-2 border-b border-zinc-200 pb-2 dark:border-zinc-800">
                        <button
                            type="button"
                            class="rounded-full px-3 py-1.5 text-xs font-bold uppercase tracking-wide {{ $activeTab === 'play' ? 'bg-emerald-600 text-white' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800' }}"
                            wire:click="$set('activeTab', 'play')"
                        >
                            Play
                        </button>
                        <button
                            type="button"
                            class="rounded-full px-3 py-1.5 text-xs font-bold uppercase tracking-wide {{ $activeTab === 'tools' ? 'bg-emerald-600 text-white' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800' }}"
                            wire:click="$set('activeTab', 'tools')"
                        >
                            Share &amp; data
                        </button>
                    </div>

                    <div class="rounded-2xl border border-emerald-200/80 bg-gradient-to-br from-emerald-50/90 to-white p-4 shadow-sm dark:border-emerald-900/40 dark:from-emerald-950/25 dark:to-zinc-950/80">
                        <p class="text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300/90">Share live</p>
                        <p class="mt-1 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">
                            Spectators can follow this session on the watch page. Create a link once, then copy it anytime.
                        </p>
                        <div class="mt-3 space-y-2">
                            @if (empty($state['shareUuid'] ?? ''))
                                <div>
                                    <button type="button" class="rounded-2xl bg-zinc-800 px-3 py-2 text-sm font-semibold text-white dark:bg-zinc-200 dark:text-zinc-900" wire:click="startSharing" wire:loading.attr="disabled" @disabled($shareBusy)>
                                        <span wire:loading.remove wire:target="startSharing">Create link</span>
                                        <span wire:loading wire:target="startSharing">Working…</span>
                                    </button>
                                </div>
                            @endif
                            @if (! empty($state['shareUuid']) && empty($state['shareSecret'] ?? ''))
                                <p class="text-xs text-amber-800 dark:text-amber-200/90">Host key missing — create a new link to share.</p>
                                <button type="button" class="rounded-2xl bg-zinc-800 px-3 py-2 text-xs font-semibold text-white dark:bg-zinc-200 dark:text-zinc-900" wire:click="startSharing" wire:loading.attr="disabled" @disabled($shareBusy)>New link</button>
                            @endif
                            @if (! empty($state['shareUuid']) && ! empty($state['shareSecret'] ?? ''))
                                <div class="flex flex-wrap gap-2">
                                    <input type="text" readonly class="min-w-0 flex-1 rounded border border-zinc-200 bg-white px-2 py-1.5 text-xs dark:border-zinc-600 dark:bg-zinc-950" value="{{ $this->shareWatchUrl() }}" />
                                    <button type="button" class="rounded-xl border border-zinc-300 px-3 py-1.5 text-xs font-semibold dark:border-zinc-600" wire:click="copyShareLink">
                                        {{ $shareCopied ? 'Copied' : 'Copy' }}
                                    </button>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 text-xs">
                                    <span class="text-zinc-600 dark:text-zinc-400">Live updates:</span>
                                    @if (! empty($state['shareSyncEnabled']))
                                        <button type="button" class="font-semibold text-emerald-700 underline dark:text-emerald-400" wire:click="pauseSharing">On (tap to pause)</button>
                                    @else
                                        <button type="button" class="font-semibold text-zinc-500 underline" wire:click="resumeSharing">Off (tap to resume)</button>
                                    @endif
                                </div>
                                <button type="button" class="text-xs text-zinc-500 underline" wire:confirm="Stop sharing? Watch links will stop working." wire:click="revokeSharing">Stop sharing</button>
                            @endif
                        </div>
                        @if (! empty($state['shareError'] ?? ''))
                            <p class="mt-2 text-xs text-red-600">{{ $state['shareError'] }}</p>
                        @endif
                    </div>

                    @if ($activeTab === 'tools')
                        <div class="space-y-4 rounded-2xl border border-zinc-200/90 bg-zinc-50/50 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Export / import</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <button type="button" class="rounded-xl bg-zinc-800 px-3 py-2 text-sm font-semibold text-white dark:bg-zinc-200 dark:text-zinc-900" wire:click="exportJson">
                                        Export JSON
                                    </button>
                                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-zinc-300 px-3 py-2 text-sm font-semibold dark:border-zinc-600">
                                        <span>Import JSON</span>
                                        <input type="file" class="sr-only" accept=".json,.txt,application/json,text/plain" wire:model="importFile" wire:change="importJson" />
                                    </label>
                                </div>
                                @if (! empty($state['importError'] ?? ''))
                                    <p class="mt-2 text-xs text-red-600">{{ $state['importError'] }}</p>
                                @endif
                            </div>
                            <div class="border-t border-zinc-200 pt-4 dark:border-zinc-800">
                                <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Danger zone</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        class="rounded-xl border border-amber-300 px-3 py-2 text-xs font-semibold text-amber-900 dark:border-amber-800 dark:text-amber-200"
                                        wire:confirm="Clear all scores and matches on this session?"
                                        wire:click="resetSession"
                                    >
                                        Reset session
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-xl border border-red-300 px-3 py-2 text-xs font-semibold text-red-800 dark:border-red-900 dark:text-red-300"
                                        wire:confirm="Erase all GameQ data and return to the session list?"
                                        wire:click="fullReset"
                                    >
                                        Full reset
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($activeTab === 'play')
                        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                            <button type="button" class="touch-manipulation min-h-11 flex-1 rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white shadow-sm hover:bg-emerald-500 active:scale-[0.98] sm:flex-none sm:px-5 sm:py-2.5" wire:click="fillCourts">Fill courts</button>
                            <button type="button" class="touch-manipulation min-h-11 flex-1 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-semibold text-zinc-700 hover:bg-zinc-50 active:scale-[0.98] dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800 sm:flex-none sm:rounded-xl sm:py-2.5" wire:click="syncQueueFromIdle">Sync queue</button>
                        </div>

                        <div class="rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900/60">
                            <label class="block text-sm font-medium text-zinc-800 dark:text-zinc-200" for="gq-session-time-limit">Match timer limit (minutes)</label>
                            <p class="mt-1 text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">0</span> = no countdown. Remaining time is computed from this limit and each court’s clock so the two always add up.
                            </p>
                            <div class="mt-3 flex flex-wrap items-end gap-3">
                                <input
                                    id="gq-session-time-limit"
                                    type="number"
                                    min="0"
                                    max="120"
                                    step="1"
                                    wire:model.live="state.timeLimitMinutes"
                                    class="h-11 w-24 rounded-xl border border-zinc-200 bg-white px-3 text-center text-base font-semibold tabular-nums dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                                />
                                <span class="pb-2 text-xs text-zinc-500">min</span>
                            </div>
                        </div>

                        @if (count($state['players'] ?? []) >= 2)
                            <div class="rounded-2xl border border-zinc-200/90 bg-gradient-to-br from-white to-zinc-50/80 p-4 shadow-sm dark:border-zinc-700 dark:from-zinc-900/90 dark:to-zinc-950/80">
                                <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Player vs player</p>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-500">Singles matches only (same as full head-to-head in Standings &amp; log).</p>
                                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                    <label class="block min-w-0 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                        Player A
                                        <select wire:model.live="state.h2hPlayerA" class="mt-1.5 min-h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100">
                                            @foreach ($state['players'] ?? [] as $hp)
                                                <option value="{{ $hp['id'] }}">{{ $hp['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label class="block min-w-0 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                        Player B
                                        <select wire:model.live="state.h2hPlayerB" class="mt-1.5 min-h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100">
                                            @foreach ($state['players'] ?? [] as $hp)
                                                <option value="{{ $hp['id'] }}" @disabled(($state['h2hPlayerA'] ?? '') === (string) ($hp['id'] ?? ''))>{{ $hp['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                </div>
                                <div class="mt-4 rounded-xl border border-emerald-200/60 bg-emerald-50/50 px-4 py-3 text-center dark:border-emerald-900/40 dark:bg-emerald-950/20">
                                    @php
                                        $ha = $state['h2hPlayerA'] ?? '';
                                        $hb = $state['h2hPlayerB'] ?? '';
                                        $pair = $this->pairH2hSummary($ha !== '' ? $ha : null, $hb !== '' ? $hb : null);
                                    @endphp
                                    @if ($ha !== '' && $hb !== '' && $ha !== $hb)
                                        <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">
                                            {{ $eq->playerLabel($ha) }}
                                            <span class="mx-2 font-normal text-zinc-400">vs</span>
                                            {{ $eq->playerLabel($hb) }}
                                        </p>
                                        <p class="mt-2 font-mono text-2xl font-bold tabular-nums text-emerald-800 dark:text-emerald-300">
                                            {{ $pair['winsA'] ?? 0 }}
                                            <span class="mx-2 text-base font-normal text-zinc-400">–</span>
                                            {{ $pair['winsB'] ?? 0 }}
                                        </p>
                                    @else
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Pick two different players.</p>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div class="space-y-4">
                            <section class="min-w-0 space-y-3">
                                @foreach ($state['courts'] ?? [] as $i => $court)
                                    @php
                                        $run = $court['timerRunState'] ?? 'running';
                                        $rs = $court ? $eq->remainingSeconds($court) : null;
                                    @endphp
                                    <div
                                        class="mx-auto w-full max-w-4xl overflow-hidden rounded-xl border transition-shadow xl:max-w-5xl {{ $court ? 'border-emerald-200/90 bg-gradient-to-b from-emerald-50/90 via-white to-white shadow-md shadow-emerald-900/[0.06] dark:border-emerald-800/45 dark:from-emerald-950/35 dark:via-zinc-900/80 dark:to-zinc-950 dark:shadow-emerald-950/25' : 'border-dashed border-zinc-200 bg-zinc-50/40 dark:border-zinc-700 dark:bg-zinc-900/35' }}"
                                        wire:key="court-{{ $i }}"
                                    >
                                        <div class="border-b {{ $court ? 'border-emerald-200/60 bg-emerald-600/[0.06] dark:border-emerald-900/50 dark:bg-emerald-500/[0.04]' : 'border-zinc-200/80 dark:border-zinc-700' }}">
                                            <div class="flex flex-col gap-3 px-3 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4 sm:py-3.5">
                                                <span
                                                    class="inline-flex w-fit items-center rounded-full px-2.5 py-0.5 text-[9px] font-bold uppercase tracking-wider {{ $court ? 'bg-emerald-600/15 text-emerald-900 dark:bg-emerald-500/20 dark:text-emerald-100' : 'bg-zinc-200/90 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300' }}"
                                                >
                                                    Court {{ $i + 1 }}
                                                </span>
                                                @if ($court)
                                                    <div class="flex w-full flex-wrap items-end justify-end gap-4 sm:w-auto sm:gap-8">
                                                        @if ((int) ($state['timeLimitMinutes'] ?? 0) > 0)
                                                            <div class="min-w-0 flex-1 sm:flex-none sm:text-right">
                                                                <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Remaining</p>
                                                                <p
                                                                    class="font-mono text-2xl font-bold tabular-nums leading-none tracking-tight sm:text-3xl {{ ($rs === 0) ? 'text-amber-600 dark:text-amber-300' : 'text-emerald-700 dark:text-emerald-300' }}"
                                                                    title="Timer remaining"
                                                                >
                                                                    {{ $this->courtRemainingDisplay($court) }}
                                                                </p>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                            @if ($court)
                                                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-emerald-200/50 px-3 py-2 dark:border-emerald-900/40">
                                                    @if ($run === 'paused')
                                                        <span class="mr-auto text-[10px] font-bold uppercase tracking-wide text-amber-800 dark:text-amber-200/90">Paused</span>
                                                    @endif
                                                    @if ($run === 'stopped')
                                                        <span class="mr-auto text-[10px] font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Timer stopped</span>
                                                    @endif
                                                    @if ($run === 'running')
                                                        <div class="flex flex-wrap gap-1.5">
                                                            <button type="button" class="touch-manipulation rounded-lg border border-emerald-300/80 bg-white px-2.5 py-1.5 text-[10px] font-bold uppercase tracking-wide text-emerald-900 hover:bg-emerald-50 dark:border-emerald-800 dark:bg-zinc-900 dark:text-emerald-100 dark:hover:bg-emerald-950/50" wire:click="pauseCourtTimer({{ $i }})">Pause</button>
                                                            <button type="button" class="touch-manipulation rounded-lg border border-zinc-300/80 bg-white px-2.5 py-1.5 text-[10px] font-bold uppercase tracking-wide text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700" wire:click="stopCourtTimer({{ $i }})">Stop</button>
                                                        </div>
                                                    @endif
                                                    @if ($run === 'paused')
                                                        <div class="flex flex-wrap gap-1.5">
                                                            <button type="button" class="touch-manipulation rounded-lg border border-emerald-300/80 bg-white px-2.5 py-1.5 text-[10px] font-bold uppercase tracking-wide text-emerald-900 hover:bg-emerald-50 dark:border-emerald-800 dark:bg-zinc-900 dark:text-emerald-100 dark:hover:bg-emerald-950/50" wire:click="resumeCourtTimer({{ $i }})">Resume</button>
                                                            <button type="button" class="touch-manipulation rounded-lg border border-zinc-300/80 bg-white px-2.5 py-1.5 text-[10px] font-bold uppercase tracking-wide text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700" wire:click="stopCourtTimer({{ $i }})">Stop</button>
                                                        </div>
                                                    @endif
                                                    @if ($run === 'stopped')
                                                        <button type="button" class="touch-manipulation rounded-lg bg-emerald-600 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wide text-white hover:bg-emerald-500 dark:hover:bg-emerald-500" wire:click="startCourtTimer({{ $i }})">Start</button>
                                                    @endif
                                                </div>
                                            @endif
                                            @if ($court && (int) ($state['timeLimitMinutes'] ?? 0) > 0)
                                                <div class="space-y-2 border-t border-emerald-100/90 bg-white/60 px-3 py-3 dark:border-emerald-900/35 dark:bg-zinc-950/30">
                                                    <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Edit clock (this court)</p>
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <button type="button" class="touch-manipulation rounded-lg border border-zinc-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-zinc-800 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800" wire:click="bumpCourtRemainingMinutes({{ $i }}, 1)">+1 min left</button>
                                                        <button type="button" class="touch-manipulation rounded-lg border border-zinc-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-zinc-800 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800" wire:click="bumpCourtRemainingMinutes({{ $i }}, -1)">−1 min left</button>
                                                        <span class="text-xs text-zinc-500 dark:text-zinc-500">Set remaining</span>
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            step="0.5"
                                                            max="{{ (int) ($state['timeLimitMinutes'] ?? 0) }}"
                                                            placeholder="max {{ (int) ($state['timeLimitMinutes'] ?? 0) }}"
                                                            class="h-9 w-[5.5rem] rounded-lg border border-zinc-200 bg-white px-2 text-center text-sm font-semibold tabular-nums dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                                                            wire:model.live="state.courtRemainingInput.{{ $i }}"
                                                        />
                                                        <span class="text-xs text-zinc-500">min</span>
                                                        <button type="button" class="touch-manipulation rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-500" wire:click="applyCourtRemainingMinutes({{ $i }})">Apply</button>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        @if (! $court)
                                            <p class="px-3 py-5 text-center text-xs text-zinc-500 dark:text-zinc-400">
                                                Open slot — use <span class="font-semibold text-zinc-700 dark:text-zinc-300">Fill courts</span>
                                            </p>
                                        @else
                                            <div class="space-y-2 p-2.5 sm:p-3">
                                                <div class="mx-auto grid w-full max-w-3xl grid-cols-1 gap-2 sm:grid-cols-[1fr_auto_1fr] sm:items-stretch sm:gap-2">
                                                    <div class="flex min-w-0 flex-col justify-center rounded-lg border border-emerald-200/70 bg-white/80 px-3 py-3 shadow-sm dark:border-emerald-900/40 dark:bg-zinc-950/60">
                                                        <p class="text-[9px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400/90">Side A</p>
                                                        <p class="mt-1 font-display text-base font-bold leading-snug tracking-tight text-zinc-900 sm:text-lg dark:text-zinc-100">
                                                            {{ $eq->sideLabelsWithStandings($court['sideA'] ?? []) }}
                                                        </p>
                                                    </div>
                                                    <div class="flex items-center justify-center py-0.5 sm:w-10 sm:shrink-0 sm:self-center sm:py-0">
                                                        <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">vs</span>
                                                    </div>
                                                    <div class="flex min-w-0 flex-col justify-center rounded-lg border border-violet-200/70 bg-white/80 px-3 py-3 shadow-sm dark:border-violet-900/40 dark:bg-zinc-950/60">
                                                        <p class="text-[9px] font-bold uppercase tracking-wider text-violet-700 dark:text-violet-400/90">Side B</p>
                                                        <p class="mt-1 font-display text-base font-bold leading-snug tracking-tight text-zinc-900 sm:text-lg dark:text-zinc-100">
                                                            {{ $eq->sideLabelsWithStandings($court['sideB'] ?? []) }}
                                                        </p>
                                                    </div>
                                                </div>
                                                <details class="mx-auto w-full max-w-3xl rounded-lg border border-zinc-200/80 bg-white px-3 py-2 dark:border-zinc-600 dark:bg-zinc-800/40">
                                                    <summary wire:click="initCourtLineupDraft({{ $i }})" class="cursor-pointer list-none text-xs font-semibold text-zinc-700 marker:content-none dark:text-zinc-200 [&::-webkit-details-marker]:hidden">
                                                        Edit lineup
                                                    </summary>
                                                    @if (! empty($state['courtLineupDraft'][$i] ?? null))
                                                        <div class="mt-3 space-y-3" wire:click.stop>
                                                            @if (! empty($state['lineupEditError'] ?? ''))
                                                                <p class="text-xs font-medium text-red-600 dark:text-red-400">{{ $state['lineupEditError'] }}</p>
                                                            @endif
                                                            @php $slots = (($state['mode'] ?? 'singles') === 'singles') ? [0] : [0, 1]; @endphp
                                                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                                <div class="space-y-2">
                                                                    <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Side A</p>
                                                                    @foreach ($slots as $slot)
                                                                        <select class="w-full rounded-lg border border-zinc-200 bg-white px-2 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100" wire:model.live="state.courtLineupDraft.{{ $i }}.a.{{ $slot }}">
                                                                            <option value="">—</option>
                                                                            @foreach ($state['players'] ?? [] as $pl)
                                                                                @if (empty($pl['disabled']))
                                                                                    <option value="{{ $pl['id'] }}">{{ $pl['name'] }}</option>
                                                                                @endif
                                                                            @endforeach
                                                                        </select>
                                                                    @endforeach
                                                                </div>
                                                                <div class="space-y-2">
                                                                    <p class="text-[10px] font-bold uppercase tracking-wider text-violet-700 dark:text-violet-400">Side B</p>
                                                                    @foreach ($slots as $slot)
                                                                        <select class="w-full rounded-lg border border-zinc-200 bg-white px-2 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100" wire:model.live="state.courtLineupDraft.{{ $i }}.b.{{ $slot }}">
                                                                            <option value="">—</option>
                                                                            @foreach ($state['players'] ?? [] as $pl)
                                                                                @if (empty($pl['disabled']))
                                                                                    <option value="{{ $pl['id'] }}">{{ $pl['name'] }}</option>
                                                                                @endif
                                                                            @endforeach
                                                                        </select>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                            <button type="button" class="touch-manipulation rounded-lg bg-zinc-800 px-3 py-2 text-xs font-bold text-white hover:bg-zinc-700 dark:bg-zinc-200 dark:text-zinc-900 dark:hover:bg-white" wire:click="applyCourtLineupDraft({{ $i }})">Apply lineup</button>
                                                        </div>
                                                    @endif
                                                </details>
                                                <div class="mx-auto w-full max-w-2xl rounded-lg border border-zinc-200/90 bg-zinc-50/95 px-2.5 py-2 dark:border-zinc-700 dark:bg-zinc-950/90">
                                                    <div class="flex flex-col items-stretch gap-2 sm:flex-row sm:items-center sm:justify-center sm:gap-3 lg:gap-4">
                                                        <div class="flex items-center justify-center gap-2 sm:gap-2.5">
                                                            <label class="sr-only" for="gq-score-a-{{ $i }}">Side A score</label>
                                                            <input
                                                                id="gq-score-a-{{ $i }}"
                                                                type="number"
                                                                min="0"
                                                                inputmode="numeric"
                                                                class="h-9 w-[4.25rem] rounded-lg border border-zinc-200 bg-white px-1 text-center text-lg font-bold tabular-nums text-zinc-900 shadow-inner transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-50 dark:focus:border-emerald-400 dark:focus:ring-emerald-400/25"
                                                                wire:model.live="state.scoreDraft.{{ $i }}.a"
                                                            />
                                                            <span class="select-none text-xl font-light leading-none text-zinc-300 dark:text-zinc-600" aria-hidden="true">–</span>
                                                            <label class="sr-only" for="gq-score-b-{{ $i }}">Side B score</label>
                                                            <input
                                                                id="gq-score-b-{{ $i }}"
                                                                type="number"
                                                                min="0"
                                                                inputmode="numeric"
                                                                class="h-9 w-[4.25rem] rounded-lg border border-zinc-200 bg-white px-1 text-center text-lg font-bold tabular-nums text-zinc-900 shadow-inner transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-50 dark:focus:border-emerald-400 dark:focus:ring-emerald-400/25"
                                                                wire:model.live="state.scoreDraft.{{ $i }}.b"
                                                            />
                                                        </div>
                                                        <div class="flex flex-wrap items-center justify-center gap-1.5 sm:shrink-0">
                                                            <button type="button" class="min-h-8 min-w-[5.5rem] rounded-lg bg-emerald-600 px-3 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-500 active:scale-[0.98] dark:shadow-emerald-950/40 dark:hover:bg-emerald-500" wire:click="completeMatch({{ $i }})">Done</button>
                                                            <button type="button" class="min-h-8 rounded-lg px-2 text-xs font-semibold text-zinc-500 transition hover:bg-white hover:text-zinc-800 dark:hover:bg-zinc-800 dark:hover:text-zinc-200" wire:click="clearCourt({{ $i }})">Clear</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </section>

                            <section class="w-full rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900/60" aria-label="Queue">
                                <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Queue</p>
                                <p class="mt-1 text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">
                                    <span class="font-medium text-zinc-700 dark:text-zinc-300">Take a break</span> in the queue matches <span class="font-medium text-zinc-700 dark:text-zinc-300">Take a break</span> in the roster — skipped for <span class="font-medium text-zinc-700 dark:text-zinc-300">Fill courts</span> until cleared in the roster.
                                </p>
                                <ul class="mt-3 max-h-[min(60vh,40rem)] space-y-1 overflow-y-auto text-sm">
                                    @foreach ($state['queue'] ?? [] as $qi => $qid)
                                        <li class="flex min-h-11 flex-col gap-2 rounded-xl bg-zinc-50 px-3 py-2 dark:bg-zinc-950/80 sm:flex-row sm:items-center sm:justify-between sm:gap-3 sm:px-4" wire:key="queue-{{ $qi }}-{{ $qid }}">
                                            <span class="min-w-0 flex-1 truncate pl-0.5">{{ $qi + 1 }}. {{ $eq->playerStandingsLabel($qid) }}</span>
                                            <span class="flex shrink-0 flex-wrap items-center justify-end gap-1.5 sm:gap-0.5">
                                                <button type="button" class="touch-manipulation flex h-10 w-10 items-center justify-center rounded-lg text-base text-zinc-500 hover:bg-zinc-200 active:bg-zinc-300 dark:hover:bg-zinc-800 dark:active:bg-zinc-700" wire:click="moveQueueUp({{ $qi }})" aria-label="Move up">↑</button>
                                                <button type="button" class="touch-manipulation flex h-10 w-10 items-center justify-center rounded-lg text-base text-zinc-500 hover:bg-zinc-200 active:bg-zinc-300 dark:hover:bg-zinc-800 dark:active:bg-zinc-700" wire:click="moveQueueDown({{ $qi }})" aria-label="Move down">↓</button>
                                                <button type="button" class="touch-manipulation rounded-lg border border-amber-200/90 bg-amber-50 px-2.5 py-2 text-xs font-semibold text-amber-950 hover:bg-amber-100 active:scale-[0.98] dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100 dark:hover:bg-amber-950/50 sm:ml-1 sm:py-1.5" wire:click="toggleSkipShuffle('{{ $qid }}')">Take a break</button>
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                                @if (count($state['queue'] ?? []) === 0)
                                    <p class="mt-2 text-xs text-zinc-400">Nobody waiting</p>
                                @endif
                            </section>
                        </div>

                        <details class="rounded-2xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                            <summary class="cursor-pointer text-sm font-semibold text-zinc-800 dark:text-zinc-200">Save to account history</summary>
                            <div class="mt-4 space-y-4 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                                <div>
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                                        <input
                                            type="text"
                                            wire:model.live="historySaveTitle"
                                            class="min-w-0 flex-1 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
                                            placeholder="Optional name for this save"
                                            maxlength="120"
                                            aria-label="Optional name for saved session"
                                        />
                                        <button
                                            type="button"
                                            class="rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white disabled:opacity-50"
                                            wire:click="saveToHistory"
                                            wire:loading.attr="disabled"
                                            @disabled($historyBusy || $historySaveDisabled)
                                        >
                                            <span wire:loading.remove wire:target="saveToHistory">Save</span>
                                            <span wire:loading wire:target="saveToHistory">Saving…</span>
                                        </button>
                                    </div>
                                    @if ($historyQuota)
                                        <p class="mt-1 text-xs text-zinc-500">{{ (int) ($historyQuota['remaining'] ?? 0) }} uses left this month</p>
                                    @endif
                                    @if ($historyError !== '')
                                        <p class="mt-1 text-xs text-red-600">{{ $historyError }}</p>
                                    @endif
                                </div>
                            </div>
                        </details>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Standings & log modal --}}
    @if ($peopleModalOpen)
        <div class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4" wire:click="$set('peopleModalOpen', false)">
            <div class="absolute inset-0 z-0 bg-zinc-900/50" aria-hidden="true"></div>
            <div
                class="relative z-10 flex max-h-[min(92vh,720px)] w-full max-w-2xl flex-col rounded-t-[1.75rem] border border-zinc-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-900 sm:rounded-[1.75rem]"
                wire:click.stop
            >
                <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
                    <h2 class="font-display text-lg font-extrabold text-zinc-900 dark:text-white">Standings &amp; log</h2>
                    <button type="button" class="rounded-2xl p-2 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800" wire:click="$set('peopleModalOpen', false)" aria-label="Close">✕</button>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto p-4">
                    <div class="mb-4 flex flex-wrap gap-2 border-b border-zinc-200 pb-3 dark:border-zinc-800">
                        <button
                            type="button"
                            class="rounded-full px-3 py-1.5 text-xs font-bold uppercase tracking-wide {{ $modalTab === 'standings' ? 'bg-emerald-600 text-white' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800' }}"
                            wire:click="$set('modalTab', 'standings')"
                        >
                            Standings
                        </button>
                        <button
                            type="button"
                            class="rounded-full px-3 py-1.5 text-xs font-bold uppercase tracking-wide {{ $modalTab === 'log' ? 'bg-emerald-600 text-white' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800' }}"
                            wire:click="$set('modalTab', 'log')"
                        >
                            Head-to-head &amp; log
                        </button>
                    </div>
                    <p class="mb-4 text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">
                        Roster is on the main host screen (left on wide layouts, below the courts on narrow screens). Use this for standings, head-to-head, and recent matches.
                    </p>
                    @if ($modalTab === 'standings')
                        <div class="space-y-6">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Standings</p>
                                <ol class="mt-3 space-y-2">
                                    @foreach ($eq->rankings() as $ri => $r)
                                        <li
                                            class="flex items-center justify-between gap-3 rounded-2xl border px-3 py-3 text-sm transition dark:border-zinc-800 {{ $ri === 0 ? 'border-amber-200/90 bg-gradient-to-r from-amber-50/90 to-white dark:border-amber-900/40 dark:from-amber-950/30 dark:to-zinc-900/50' : 'border-zinc-100 bg-zinc-50/80 dark:border-zinc-800 dark:bg-zinc-950/50' }}"
                                            wire:key="rank-{{ $r['id'] }}"
                                        >
                                            <span class="flex min-w-0 items-center gap-3">
                                                <span
                                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-xs font-bold tabular-nums {{ $ri === 0 ? 'bg-amber-400/25 text-amber-950 dark:bg-amber-500/20 dark:text-amber-100' : ($ri === 1 ? 'bg-zinc-200/90 text-zinc-700 dark:bg-zinc-600 dark:text-zinc-100' : ($ri === 2 ? 'bg-orange-200/50 text-orange-950 dark:bg-orange-500/15 dark:text-orange-100' : 'bg-zinc-200/60 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400')) }}"
                                                >
                                                    {{ $ri + 1 }}
                                                </span>
                                                <span class="truncate font-semibold text-zinc-900 dark:text-zinc-100">{{ $r['name'] }}</span>
                                            </span>
                                            <span class="shrink-0 text-right tabular-nums">
                                                <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ (int) ($r['wins'] ?? 0) }}W · {{ (int) ($r['losses'] ?? 0) }}L</span>
                                                <span class="text-sm font-bold text-emerald-700 dark:text-emerald-400">{{ ! empty($r['played']) ? ($r['pct'] ?? 0).'%' : '—' }}</span>
                                            </span>
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                        </div>
                    @endif
                    @if ($modalTab === 'log')
                        <div class="space-y-6">
                            <details class="rounded-xl border border-zinc-200 p-3 dark:border-zinc-700" open>
                                <summary class="cursor-pointer text-sm font-semibold text-zinc-800 dark:text-zinc-200">Head-to-head</summary>
                                <ul class="mt-2 divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                                    @foreach ($eq->h2hRows() as $row)
                                        <li class="flex flex-wrap items-center justify-between gap-2 py-2" wire:key="h2h-{{ $row['key'] }}">
                                            <span>{{ $row['left'] }}</span>
                                            <span class="font-medium text-emerald-700 dark:text-emerald-400">{{ $row['winsLeft'] }} – {{ $row['winsRight'] }}</span>
                                            <span>{{ $row['right'] }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </details>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Recent matches</p>
                                <ul class="mt-3 max-h-48 space-y-2 overflow-y-auto pr-1">
                                    @foreach (array_reverse($state['completedMatches'] ?? []) as $mi => $m)
                                        <li
                                            class="grid grid-cols-1 items-center gap-2 rounded-xl border border-zinc-200/80 bg-white px-3 py-2.5 text-xs shadow-sm sm:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] sm:gap-3 dark:border-zinc-700 dark:bg-zinc-950/60"
                                            wire:key="match-{{ $mi }}"
                                        >
                                            <span class="min-w-0 font-medium leading-snug text-zinc-800 dark:text-zinc-200 sm:text-left">{{ $eq->sideLabelsWithStandings($m['sideA'] ?? []) }}</span>
                                            <span class="justify-self-center rounded-lg bg-zinc-100 px-2.5 py-1 font-mono text-sm font-bold tabular-nums text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100">{{ $m['scoreA'] }} – {{ $m['scoreB'] }}</span>
                                            <span class="min-w-0 font-medium leading-snug text-zinc-800 dark:text-zinc-200 sm:text-right">{{ $eq->sideLabelsWithStandings($m['sideB'] ?? []) }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
