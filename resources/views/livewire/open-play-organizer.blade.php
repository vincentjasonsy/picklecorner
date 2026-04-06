<div
    class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-10"
    x-data="pickleGameQApp()"
    x-cloak
    data-open-play-share-store="{{ route('open-play.share.store') }}"
    data-open-play-share-base="{{ url('/open-play/share') }}"
    data-open-play-watch-base="{{ url('/open-play/watch') }}"
    data-open-play-sessions-base="{{ url(route('account.open-play.sessions.index', [], false)) }}"
>
    {{-- ========== LIST: history table + create ========== --}}
    <div x-show="uiPhase === 'list'" class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="font-display text-2xl font-extrabold tracking-tight text-zinc-900 dark:text-white">
                    PickleGameQ
                </h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    Your hosted sessions — open one or start a new queue.
                </p>
            </div>
            <button
                type="button"
                class="shrink-0 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-500 dark:bg-emerald-600 dark:hover:bg-emerald-500"
                @click="startOpenPlayWizard()"
            >
                Start PickleGameQ
            </button>
        </div>

        <div
            class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80"
        >
            <p class="border-b border-zinc-100 px-4 py-2 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-500" x-show="historyQuota" x-cloak>
                <span class="font-semibold text-zinc-700 dark:text-zinc-300" x-text="historyQuota ? historyQuota.used + '/' + historyQuota.limit : ''"></span>
                uses this month (saved sessions) · resets <span x-text="formatQuotaReset(historyQuota?.resets_at)"></span>
            </p>
            <p class="px-4 py-2 text-sm text-amber-800 dark:text-amber-200/90" x-show="historySaveDisabled()" x-cloak>
                Monthly limit reached (5 uses) — open an existing save or wait until next month.
            </p>
            <p class="px-4 py-2 text-sm text-red-600 dark:text-red-400" x-show="historyError" x-text="historyError"></p>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-zinc-200 bg-zinc-50/90 dark:border-zinc-800 dark:bg-zinc-950/80">
                        <tr class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            <th class="px-4 py-3">Session</th>
                            <th class="px-4 py-3">Hosted</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        <template x-for="row in historySessions" :key="row.id">
                            <tr class="text-zinc-800 dark:text-zinc-200">
                                <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100" x-text="row.title"></td>
                                <td class="whitespace-nowrap px-4 py-3 text-zinc-600 dark:text-zinc-400" x-text="formatHistoryDate(row.created_at || row.updated_at)"></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <button
                                        type="button"
                                        class="mr-2 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-500"
                                        @click="loadHistorySession(row.id)"
                                    >
                                        Open
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-lg border border-zinc-200 px-3 py-1.5 text-xs font-semibold text-zinc-600 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-400 dark:hover:bg-zinc-800/50"
                                        @click="deleteHistorySession(row.id)"
                                    >
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="historySessions.length === 0 && !historyError" x-cloak>
                            <td colspan="3" class="px-4 py-14 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                <p class="font-medium text-zinc-700 dark:text-zinc-300">No sessions yet</p>
                                <p class="mt-2">
                                    <button type="button" class="font-semibold text-emerald-700 hover:underline dark:text-emerald-400" @click="startOpenPlayWizard()">
                                        Start your first PickleGameQ
                                    </button>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ========== SETUP WIZARD ========== --}}
    <div x-show="uiPhase === 'setup'" class="mx-auto max-w-lg space-y-8 pb-8">
        <div class="flex justify-end">
            <span class="text-xs font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                Step <span x-text="setupStep"></span> / 3
            </span>
        </div>

        <div class="flex justify-center gap-2">
            <template x-for="n in [1, 2, 3]" :key="n">
                <span
                    class="h-2 w-8 rounded-full transition-colors"
                    :class="setupStep >= n ? 'bg-emerald-500' : 'bg-zinc-200 dark:bg-zinc-700'"
                ></span>
            </template>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80">
            <div x-show="setupStep === 1" class="space-y-5">
                <h2 class="font-display text-lg font-extrabold text-zinc-900 dark:text-white">Session rules</h2>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    Format
                    <select x-model="mode" class="mt-1.5 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2.5 text-sm dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100">
                        <option value="singles">Singles</option>
                        <option value="doubles">Doubles</option>
                    </select>
                </label>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    Who plays next
                    <select x-model="shuffleMethod" class="mt-1.5 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2.5 text-sm dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100">
                        <option value="random">Random</option>
                        <option value="wins">Fewest wins first</option>
                        <option value="levels">By skill level</option>
                        <option value="teams">Fixed pairs (team on each player)</option>
                    </select>
                </label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Courts
                        <input type="number" min="1" max="8" x-model.number="courtsCount" @change="courtsCountChanged()" class="mt-1.5 w-full rounded-lg border border-zinc-200 px-3 py-2.5 text-sm dark:border-zinc-600 dark:bg-zinc-950" />
                    </label>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Timer (min, 0 = off)
                        <input type="number" min="0" max="120" x-model.number="timeLimitMinutes" class="mt-1.5 w-full rounded-lg border border-zinc-200 px-3 py-2.5 text-sm dark:border-zinc-600 dark:bg-zinc-950" />
                    </label>
                </div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400" x-show="mode === 'doubles' && shuffleMethod === 'teams'">
                    Use the same team name for partners in the next step.
                </p>
            </div>

            <div x-show="setupStep === 2" class="space-y-5" x-cloak>
                <h2 class="font-display text-lg font-extrabold text-zinc-900 dark:text-white">Players</h2>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">Add who’s in today. You can skip and add later from the host screen.</p>
                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">
                    <span x-text="players.length"></span> / <span x-text="maxPlayersPerSession"></span> players max
                </p>
                <div class="flex flex-wrap items-end gap-3">
                    <label class="min-w-[10rem] grow text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Name
                        <input type="text" x-model="newName" placeholder="Name" class="mt-1.5 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950" @keydown.enter.prevent="addPlayer()" />
                    </label>
                    <label class="w-20 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Lvl
                        <input type="number" min="1" max="10" x-model.number="newLevel" class="mt-1.5 w-full rounded-lg border border-zinc-200 px-2 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950" />
                    </label>
                    <label class="min-w-[6rem] grow text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Team
                        <input type="text" x-model="newTeamId" placeholder="Optional" class="mt-1.5 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950" />
                    </label>
                    <button type="button" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-50" :disabled="playerCapReached()" @click="addPlayer()">Add</button>
                </div>
                <ul class="max-h-48 space-y-1 overflow-y-auto text-sm" x-show="players.length > 0">
                    <template x-for="p in players" :key="p.id">
                        <li class="flex items-center justify-between rounded-lg bg-zinc-50 px-3 py-2 dark:bg-zinc-950/60">
                            <span x-text="p.name"></span>
                            <button type="button" class="text-xs text-zinc-500 hover:text-red-600" @click="removePlayer(p.id)">Remove</button>
                        </li>
                    </template>
                </ul>
                <p class="text-sm text-zinc-400" x-show="players.length === 0">No players added yet.</p>
            </div>

            <div x-show="setupStep === 3" class="space-y-5" x-cloak>
                <h2 class="font-display text-lg font-extrabold text-zinc-900 dark:text-white">Ready</h2>
                <ul class="space-y-2 text-sm text-zinc-700 dark:text-zinc-300">
                    <li><span class="text-zinc-500">Format:</span> <span class="font-medium capitalize" x-text="mode"></span></li>
                    <li><span class="text-zinc-500">Pairing:</span> <span class="font-medium" x-text="shuffleMethodLabel()"></span></li>
                    <li><span class="text-zinc-500">Courts:</span> <span class="font-medium" x-text="courtsCount"></span></li>
                    <li><span class="text-zinc-500">Players:</span> <span class="font-medium" x-text="players.length"></span></li>
                </ul>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Next: fill courts, log scores, and manage the queue. Open <span class="font-medium text-zinc-700 dark:text-zinc-300">Players</span> anytime for roster and standings.</p>
            </div>

            <div class="mt-8 flex justify-between gap-3 border-t border-zinc-100 pt-6 dark:border-zinc-800">
                <button
                    type="button"
                    class="rounded-lg border border-zinc-200 px-4 py-2.5 text-sm font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800"
                    @click="setupGoBack()"
                    x-text="setupStep === 1 ? 'All sessions' : 'Back'"
                ></button>
                <button
                    type="button"
                    class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-500"
                    x-show="setupStep < 3"
                    @click="setupGoNext()"
                >
                    Next
                </button>
                <button
                    type="button"
                    class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-500"
                    x-show="setupStep === 3"
                    x-cloak
                    @click="finishSetup()"
                >
                    Start hosting
                </button>
            </div>
        </div>
    </div>

    {{-- ========== MINIMAL HOST VIEW ========== --}}
    <div x-show="uiPhase === 'session'" class="space-y-5" x-cloak>
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 pb-4 dark:border-zinc-800">
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    class="text-sm font-semibold text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
                    @click="goToSessionList()"
                >
                    ← Sessions
                </button>
                <span class="hidden h-4 w-px bg-zinc-200 dark:bg-zinc-700 sm:block"></span>
                <h1 class="font-display text-lg font-extrabold text-zinc-900 dark:text-white">Hosting</h1>
            </div>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="rounded-lg border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                    @click="peopleModalOpen = true"
                >
                    Players
                </button>
            </div>
        </header>

        <div class="flex flex-wrap gap-2">
            <button type="button" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-500" @click="fillCourts()">Fill courts</button>
            <button type="button" class="rounded-lg border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800" @click="syncQueueFromIdle()">Sync queue</button>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <section class="space-y-3 lg:col-span-2">
                <template x-for="(court, i) in courts" :key="'court-' + i">
                    <div
                        class="rounded-xl border p-4 dark:border-zinc-700"
                        :class="court
                            ? 'border-emerald-200/80 bg-emerald-50/30 dark:border-emerald-900/40 dark:bg-emerald-950/20'
                            : 'border-dashed border-zinc-200 bg-zinc-50/50 dark:border-zinc-700 dark:bg-zinc-900/30'"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400" x-text="'Court ' + (i + 1)"></span>
                            <template x-if="court && timeLimitMinutes > 0">
                                <span
                                    class="text-xs tabular-nums text-zinc-600 dark:text-zinc-400"
                                    :class="remainingSeconds(court) === 0 ? 'text-amber-700 dark:text-amber-300' : ''"
                                    x-text="formatCountdown(remainingSeconds(court))"
                                ></span>
                            </template>
                        </div>
                        <template x-if="!court">
                            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Empty</p>
                        </template>
                        <template x-if="court">
                            <div class="mt-3 space-y-3">
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <p class="text-xs text-zinc-500">A</p>
                                        <p class="font-medium text-zinc-900 dark:text-zinc-100" x-text="sideLabels(court.sideA)"></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-zinc-500">B</p>
                                        <p class="font-medium text-zinc-900 dark:text-zinc-100" x-text="sideLabels(court.sideB)"></p>
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-end gap-2">
                                    <input type="number" min="0" class="w-16 rounded border border-zinc-200 px-2 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-950" x-model.number="getScoreDraft(i).a" aria-label="Score A" />
                                    <span class="text-zinc-400">–</span>
                                    <input type="number" min="0" class="w-16 rounded border border-zinc-200 px-2 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-950" x-model.number="getScoreDraft(i).b" aria-label="Score B" />
                                    <button type="button" class="rounded-lg bg-zinc-900 px-3 py-1.5 text-xs font-bold text-white dark:bg-zinc-100 dark:text-zinc-900" @click="completeMatch(i)">Done</button>
                                    <button type="button" class="text-xs text-zinc-500 underline" @click="clearCourt(i)">Clear</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </section>

            <aside class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900/60">
                <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Queue</p>
                <ul class="mt-3 max-h-[50vh] space-y-1 overflow-y-auto text-sm">
                    <template x-for="(qid, qi) in queue" :key="qid + '-' + qi">
                        <li class="flex items-center justify-between gap-2 rounded-md bg-zinc-50 px-2 py-1.5 dark:bg-zinc-950/80">
                            <span class="truncate" x-text="(qi + 1) + '. ' + playerLabel(qid)"></span>
                            <span class="flex shrink-0 gap-0.5">
                                <button type="button" class="rounded p-1 text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-800" @click="moveQueueUp(qi)" aria-label="Up">↑</button>
                                <button type="button" class="rounded p-1 text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-800" @click="moveQueueDown(qi)" aria-label="Down">↓</button>
                                <button type="button" class="rounded p-1 text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-800" @click="removeFromQueue(qi)" aria-label="Remove">×</button>
                            </span>
                        </li>
                    </template>
                </ul>
                <p class="mt-2 text-xs text-zinc-400" x-show="queue.length === 0">Nobody waiting</p>
            </aside>
        </div>

        <details class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
            <summary class="cursor-pointer text-sm font-semibold text-zinc-800 dark:text-zinc-200">More — share, history, backup</summary>
            <div class="mt-4 space-y-6 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Save to account history</p>
                    <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-end">
                        <input
                            type="text"
                            x-model="historySaveTitle"
                            class="min-w-0 flex-1 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
                            placeholder="Optional name"
                            maxlength="120"
                        />
                        <button
                            type="button"
                            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white disabled:opacity-50"
                            @click="saveToHistory()"
                            :disabled="historyBusy || historySaveDisabled()"
                            x-text="historyBusy ? 'Saving…' : 'Save'"
                        ></button>
                    </div>
                    <p class="mt-1 text-xs text-zinc-500" x-show="historyQuota" x-text="historyQuota ? historyQuota.remaining + ' uses left this month' : ''"></p>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Live link</p>
                    <div class="mt-2 space-y-2">
                        <div x-show="!shareUuid">
                            <button type="button" class="rounded-lg bg-zinc-800 px-3 py-2 text-sm font-semibold text-white dark:bg-zinc-200 dark:text-zinc-900" @click="startSharing()" :disabled="shareBusy">Create link</button>
                        </div>
                        <p class="text-xs text-amber-800 dark:text-amber-200/90" x-show="shareUuid && !shareSecret" x-cloak>
                            Host key missing — create a new link to share.
                        </p>
                        <button
                            type="button"
                            class="rounded-lg bg-zinc-800 px-3 py-2 text-xs font-semibold text-white dark:bg-zinc-200 dark:text-zinc-900"
                            x-show="shareUuid && !shareSecret"
                            x-cloak
                            @click="startSharing()"
                            :disabled="shareBusy"
                        >
                            New link
                        </button>
                        <div x-show="shareUuid && shareSecret" x-cloak class="flex flex-wrap gap-2">
                            <input type="text" readonly class="min-w-0 flex-1 rounded border border-zinc-200 bg-white px-2 py-1.5 text-xs dark:border-zinc-600 dark:bg-zinc-950" :value="shareWatchUrl()" />
                            <button type="button" class="rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-semibold dark:border-zinc-600" @click="copyShareLink()" x-text="shareCopied ? 'Copied' : 'Copy'"></button>
                        </div>
                        <label class="flex items-center gap-2 text-xs" x-show="shareUuid && shareSecret" x-cloak>
                            <input type="checkbox" class="rounded border-zinc-300 text-emerald-600" :checked="shareSyncEnabled" @change="$event.target.checked ? resumeSharing() : pauseSharing()" />
                            Live updates
                        </label>
                        <button type="button" class="text-xs text-zinc-500 underline" x-show="shareUuid && shareSecret" x-cloak @click="revokeSharing()">Stop sharing</button>
                    </div>
                    <p class="mt-1 text-xs text-red-600" x-show="shareError" x-text="shareError"></p>
                </div>
                <div class="flex flex-wrap gap-3 text-sm">
                    <button type="button" class="text-emerald-700 hover:underline dark:text-emerald-400" @click="exportJson()">Export</button>
                    <label class="cursor-pointer text-emerald-700 hover:underline dark:text-emerald-400">
                        Import
                        <input type="file" accept="application/json,.json" class="sr-only" @change="importJson($event)" />
                    </label>
                    <button type="button" class="text-amber-800 hover:underline dark:text-amber-300" @click="resetSession()">Clear scores</button>
                    <button type="button" class="text-zinc-500 hover:underline dark:text-zinc-400" @click="fullReset()">Delete all</button>
                </div>
                <p class="text-xs text-red-600" x-show="importError" x-text="importError"></p>
            </div>
        </details>
    </div>

    {{-- Players modal (full roster, standings, H2H) --}}
    <div
        x-show="peopleModalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-end justify-center bg-zinc-900/50 p-0 sm:items-center sm:p-4"
        @keydown.escape.window="peopleModalOpen = false"
        @click="peopleModalOpen = false"
    >
        <div
            class="flex max-h-[min(92vh,720px)] w-full max-w-2xl flex-col rounded-t-2xl border border-zinc-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-900 sm:rounded-2xl"
            @click.stop
        >
            <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
                <h2 class="font-display text-lg font-extrabold text-zinc-900 dark:text-white">Players &amp; results</h2>
                <button type="button" class="rounded-lg p-2 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800" @click="peopleModalOpen = false" aria-label="Close">✕</button>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto p-4">
                <div class="mb-6 flex gap-1 rounded-lg bg-zinc-100 p-1 dark:bg-zinc-800">
                    <button
                        type="button"
                        class="flex-1 rounded-md px-3 py-2 text-sm font-semibold transition"
                        :class="activeTab === 'people' ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-700 dark:text-white' : 'text-zinc-600 dark:text-zinc-400'"
                        @click="activeTab = 'people'"
                    >
                        Roster
                    </button>
                    <button
                        type="button"
                        class="flex-1 rounded-md px-3 py-2 text-sm font-semibold transition"
                        :class="activeTab === 'stats' ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-700 dark:text-white' : 'text-zinc-600 dark:text-zinc-400'"
                        @click="activeTab = 'stats'"
                    >
                        Standings &amp; log
                    </button>
                </div>

                <div x-show="activeTab === 'people'" class="space-y-6">
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">
                        <span x-text="players.length"></span> / <span x-text="maxPlayersPerSession"></span> players max
                    </p>
                    <div class="flex flex-wrap items-end gap-3">
                        <label class="min-w-[10rem] grow text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Name
                            <input type="text" x-model="newName" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950" placeholder="Name" @keydown.enter.prevent="addPlayer()" />
                        </label>
                        <label class="w-20 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Lvl
                            <input type="number" min="1" max="10" x-model.number="newLevel" class="mt-1 w-full rounded-lg border border-zinc-200 px-2 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950" />
                        </label>
                        <label class="min-w-[6rem] grow text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Team
                            <input type="text" x-model="newTeamId" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950" placeholder="Optional" />
                        </label>
                        <button type="button" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-50" :disabled="playerCapReached()" @click="addPlayer()">Add</button>
                    </div>
                    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                        <table class="w-full min-w-[28rem] text-left text-sm">
                            <thead class="border-b border-zinc-200 bg-zinc-50 text-xs font-bold uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-400">
                                <tr>
                                    <th class="px-3 py-2">Name</th>
                                    <th class="px-3 py-2">Lvl</th>
                                    <th class="px-3 py-2">Team</th>
                                    <th class="px-3 py-2">W–L</th>
                                    <th class="px-3 py-2">In</th>
                                    <th class="px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                <template x-for="p in players" :key="p.id">
                                    <tr :class="p.disabled ? 'opacity-50' : ''">
                                        <td class="px-3 py-2">
                                            <input type="text" x-model="p.name" class="w-full min-w-[6rem] rounded border border-transparent bg-transparent py-0.5 text-sm focus:border-emerald-500 dark:text-zinc-100" />
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="number" min="1" max="10" x-model.number="p.level" class="w-12 rounded border border-zinc-200 px-1 py-0.5 dark:border-zinc-600 dark:bg-zinc-950" />
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="text" x-model="p.teamId" class="w-full max-w-[6rem] rounded border border-zinc-200 px-1 py-0.5 text-xs dark:border-zinc-600 dark:bg-zinc-950" placeholder="—" />
                                        </td>
                                        <td class="px-3 py-2 tabular-nums text-zinc-600 dark:text-zinc-400" x-text="p.wins + '–' + p.losses"></td>
                                        <td class="px-3 py-2">
                                            <label class="inline-flex items-center gap-1 text-xs">
                                                <input type="checkbox" :checked="!p.disabled" @change="toggleDisabled(p.id)" class="rounded border-zinc-300 text-emerald-600" />
                                            </label>
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <button type="button" class="text-xs text-zinc-500 hover:text-red-600" @click="removePlayer(p.id)">Remove</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-sm text-zinc-500" x-show="players.length === 0">No players yet.</p>
                </div>

                <div x-show="activeTab === 'stats'" class="space-y-6" x-cloak>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Standings</p>
                        <ol class="mt-2 space-y-1 text-sm">
                            <template x-for="(r, ri) in rankings()" :key="r.id">
                                <li class="flex justify-between rounded-lg bg-zinc-50 px-3 py-2 dark:bg-zinc-950/60">
                                    <span><span class="text-zinc-500" x-text="(ri + 1) + '.'"></span> <span class="font-medium" x-text="r.name"></span></span>
                                    <span class="tabular-nums text-zinc-600 dark:text-zinc-400">
                                        <span x-text="r.wins + 'W ' + r.losses + 'L'"></span>
                                        <span class="ml-2 text-emerald-700 dark:text-emerald-400" x-text="r.played ? r.pct + '%' : '—'"></span>
                                    </span>
                                </li>
                            </template>
                        </ol>
                    </div>
                    <details class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                        <summary class="cursor-pointer text-sm font-semibold text-zinc-800 dark:text-zinc-200">Head-to-head</summary>
                        <ul class="mt-2 divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                            <template x-for="row in h2hRows()" :key="row.key">
                                <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                                    <span x-text="row.left"></span>
                                    <span class="font-medium text-emerald-700 dark:text-emerald-400" x-text="row.winsLeft + ' – ' + row.winsRight"></span>
                                    <span x-text="row.right"></span>
                                </li>
                            </template>
                        </ul>
                    </details>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Recent matches</p>
                        <ul class="mt-2 max-h-40 space-y-1 overflow-y-auto text-xs text-zinc-600 dark:text-zinc-400">
                            <template x-for="(m, mi) in [...completedMatches].reverse()" :key="mi">
                                <li class="rounded border border-zinc-100 px-2 py-1 dark:border-zinc-800" x-text="sideLabels(m.sideA) + ' ' + m.scoreA + '–' + m.scoreB + ' ' + sideLabels(m.sideB)"></li>
                            </template>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
