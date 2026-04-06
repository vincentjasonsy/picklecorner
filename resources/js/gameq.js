/**
 * GameQ organizer — runs fully in the browser with localStorage (works offline after first load).
 */
const STORAGE_KEY = 'court_booking_gameq_v1';

/** Previous key (PickleGameQ); migrated once on read. */
const STORAGE_KEY_LEGACY = 'court_booking_picklegameq_v1';

/** Max roster / queue size for one session (must match OpenPlaySession::MAX_PLAYERS_PER_SESSION). */
const MAX_PLAYERS_PER_SESSION = 55;

function newId() {
    if (typeof crypto !== 'undefined' && crypto.randomUUID) {
        return crypto.randomUUID();
    }
    return `p_${Date.now()}_${Math.random().toString(36).slice(2, 9)}`;
}

function sideKey(ids) {
    return [...ids].map(String).sort().join(',');
}

function h2hStorageKey(sideA, sideB) {
    const a = sideKey(sideA);
    const b = sideKey(sideB);
    return a < b ? `${a}||${b}` : `${b}||${a}`;
}

function shuffleInPlace(arr) {
    for (let i = arr.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [arr[i], arr[j]] = [arr[j], arr[i]];
    }
    return arr;
}

function defaultState() {
    return {
        mode: 'singles',
        shuffleMethod: 'random',
        courtsCount: 2,
        timeLimitMinutes: 0,
        players: [],
        queue: [],
        courts: [],
        completedMatches: [],
        h2h: {},
        shareUuid: '',
        shareSecret: '',
        shareSyncEnabled: false,
        uiPhase: 'list',
        setupStep: 1,
    };
}

function loadState() {
    try {
        let raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) {
            raw = localStorage.getItem(STORAGE_KEY_LEGACY);
            if (raw) {
                localStorage.setItem(STORAGE_KEY, raw);
                localStorage.removeItem(STORAGE_KEY_LEGACY);
            }
        }
        if (!raw) {
            return defaultState();
        }
        const data = JSON.parse(raw);
        if (!data || typeof data !== 'object') {
            return defaultState();
        }
        return {
            ...defaultState(),
            ...data,
            players: Array.isArray(data.players) ? data.players : [],
            queue: Array.isArray(data.queue) ? data.queue : [],
            courts: Array.isArray(data.courts) ? data.courts : [],
            completedMatches: Array.isArray(data.completedMatches) ? data.completedMatches : [],
            h2h: data.h2h && typeof data.h2h === 'object' ? data.h2h : {},
            shareUuid: typeof data.shareUuid === 'string' ? data.shareUuid : '',
            shareSecret: typeof data.shareSecret === 'string' ? data.shareSecret : '',
            shareSyncEnabled: Boolean(data.shareSyncEnabled),
            uiPhase:
                typeof data.uiPhase === 'string' ? data.uiPhase : defaultState().uiPhase,
            setupStep:
                typeof data.setupStep === 'number' && data.setupStep >= 1
                    ? data.setupStep
                    : 1,
        };
    } catch {
        return defaultState();
    }
}

/** Plain session token from the csrf-token meta (or cookie fallback). Send as X-CSRF-TOKEN; X-XSRF-TOKEN expects the encrypted XSRF cookie value. */
function readCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    const fromMeta = meta?.getAttribute('content');
    if (fromMeta) {
        return fromMeta;
    }
    const m = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    return m ? decodeURIComponent(m[1]) : '';
}

/** From layouts::tool-focus @auth script (preferred). */
function gameqEndpointsFromWindow() {
    const w =
        typeof window !== 'undefined' ? window.__GAMEQ_ENDPOINTS : null;
    if (!w || typeof w !== 'object') {
        return {
            shareStore: '',
            shareApiBase: '',
            watchBase: '',
            sessionsBase: '',
        };
    }

    return {
        shareStore: String(w.shareStore ?? '').trim(),
        shareApiBase: String(w.shareApiBase ?? '').trim(),
        watchBase: String(w.watchBase ?? '').trim(),
        sessionsBase: String(w.sessionsBase ?? '').trim(),
    };
}

function gameqDataAttr(el, name) {
    if (!el || typeof el.getAttribute !== 'function') {
        return '';
    }

    return String(el.getAttribute(name) ?? '').trim();
}

/** Fallback: data-* on the Alpine root (see open-play-organizer.blade.php). */
function gameqEndpointsFromElement(el) {
    return {
        shareStore: gameqDataAttr(el, 'data-open-play-share-store'),
        shareApiBase: gameqDataAttr(el, 'data-open-play-share-base'),
        watchBase: gameqDataAttr(el, 'data-open-play-watch-base'),
        sessionsBase: gameqDataAttr(el, 'data-open-play-sessions-base'),
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('gameqApp', () => ({
        saveTimer: null,
        tick: 0,
        activeTab: 'play',
        peopleModalOpen: false,
        maxPlayersPerSession: MAX_PLAYERS_PER_SESSION,
        newName: '',
        newLevel: 3,
        newTeamId: '',
        scoreDraft: {},
        importError: '',
        shareError: '',
        shareBusy: false,
        shareCopied: false,
        shareSyncTimer: null,
        historySessions: [],
        historyError: '',
        historyBusy: false,
        historySaveTitle: '',
        historyQuota: null,

        ...defaultState(),

        init() {
            const s = loadState();
            Object.assign(this, s);
            this.normalizePlayerCap();
            if (!['list', 'setup', 'session'].includes(this.uiPhase)) {
                const hasActivity =
                    this.players.length > 0 ||
                    (Array.isArray(this.courts) &&
                        this.courts.some(Boolean)) ||
                    this.queue.length > 0 ||
                    this.completedMatches.length > 0;
                this.uiPhase = hasActivity ? 'session' : 'list';
            }
            this.setupStep =
                this.setupStep >= 1 && this.setupStep <= 3 ? this.setupStep : 1;
            this.ensureCourtSlots();
            const tickId = setInterval(() => {
                this.tick += 1;
            }, 1000);
            this.$el.addEventListener('alpine:destroy', () =>
                clearInterval(tickId),
            );
            this.$watch(
                () => JSON.stringify(this.serialize()),
                () => this.scheduleSave(),
            );
            this.refreshHistorySessions().then(() =>
                this.maybeLoadFromQueryParam(),
            );
        },

        _gameqEndpoints() {
            const fromWin = gameqEndpointsFromWindow();
            const fromEl = gameqEndpointsFromElement(this.$el);

            return {
                shareStore: fromWin.shareStore || fromEl.shareStore,
                shareApiBase: fromWin.shareApiBase || fromEl.shareApiBase,
                watchBase: fromWin.watchBase || fromEl.watchBase,
                sessionsBase: fromWin.sessionsBase || fromEl.sessionsBase,
            };
        },

        sessionsApiBase() {
            return String(this._gameqEndpoints().sessionsBase).replace(/\/$/, '');
        },

        async refreshHistorySessions() {
            const base = this.sessionsApiBase();
            if (!base) {
                return;
            }
            this.historyError = '';
            try {
                const r = await fetch(base, {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                });
                if (!r.ok) {
                    throw new Error('bad response');
                }
                const j = await r.json();
                this.historySessions = Array.isArray(j.sessions)
                    ? j.sessions
                    : [];
                this.historyQuota = j.quota ?? null;
            } catch {
                this.historyError = 'Could not load your session history.';
            }
        },

        historySaveDisabled() {
            return (
                this.historyQuota != null &&
                Number(this.historyQuota.remaining) <= 0
            );
        },

        formatQuotaReset(iso) {
            if (!iso) {
                return '';
            }
            try {
                const d = new Date(iso);

                return d.toLocaleDateString(undefined, {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                });
            } catch {
                return '';
            }
        },

        async maybeLoadFromQueryParam() {
            const params = new URLSearchParams(window.location.search);
            const loadId = params.get('load');
            if (!loadId || !/^\d+$/.test(loadId)) {
                return;
            }
            await this.loadHistorySession(Number.parseInt(loadId, 10));
            const url = new URL(window.location.href);
            url.searchParams.delete('load');
            const next =
                url.pathname +
                (url.searchParams.toString()
                    ? `?${url.searchParams.toString()}`
                    : '') +
                url.hash;
            window.history.replaceState({}, '', next);
        },

        async saveToHistory() {
            const base = this.sessionsApiBase();
            if (!base) {
                return;
            }
            this.historyBusy = true;
            this.historyError = '';
            try {
                const title = (this.historySaveTitle || '').trim() || null;
                const r = await fetch(base, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': readCsrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        title,
                        data: this.sharePayload(),
                    }),
                });
                const j = await r.json().catch(() => ({}));
                if (!r.ok) {
                    this.historyError =
                        j.message || 'Could not save to your account.';
                    if (j.quota) {
                        this.historyQuota = j.quota;
                    }
                    return;
                }
                if (j.quota) {
                    this.historyQuota = j.quota;
                }
                this.historySaveTitle = '';
                await this.refreshHistorySessions();
            } catch {
                this.historyError =
                    'Could not save (check your connection and try again).';
            } finally {
                this.historyBusy = false;
            }
        },

        async loadHistorySession(id) {
            const base = this.sessionsApiBase();
            if (!base) {
                return;
            }
            this.historyError = '';
            try {
                const r = await fetch(`${base}/${id}`, {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                });
                const j = await r.json().catch(() => ({}));
                if (!r.ok) {
                    this.historyError =
                        j.message || 'Could not load that session.';
                    return;
                }
                if (!j.session?.payload) {
                    this.historyError = 'Invalid session data.';
                    return;
                }
                this.applyImportedPayload(j.session.payload, {
                    clearShare: true,
                });
                this.importError = '';
                this.activeTab = 'play';
                this.uiPhase = 'session';
                this.persist();
            } catch {
                this.historyError = 'Could not load (check your connection).';
            }
        },

        async deleteHistorySession(id) {
            if (
                !confirm(
                    'Remove this session from your history? Your current on-screen setup is not affected.',
                )
            ) {
                return;
            }
            const base = this.sessionsApiBase();
            if (!base) {
                return;
            }
            this.historyError = '';
            try {
                const r = await fetch(`${base}/${id}`, {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': readCsrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                if (!r.ok) {
                    const j = await r.json().catch(() => ({}));
                    this.historyError =
                        j.message || 'Could not remove that session.';
                    return;
                }
                await this.refreshHistorySessions();
            } catch {
                this.historyError = 'Could not remove (check your connection).';
            }
        },

        formatHistoryDate(iso) {
            if (!iso) {
                return '';
            }
            try {
                const d = new Date(iso);

                return d.toLocaleString(undefined, {
                    dateStyle: 'medium',
                    timeStyle: 'short',
                });
            } catch {
                return String(iso);
            }
        },

        applyImportedPayload(data, options = {}) {
            const clearShare = options.clearShare !== false;
            if (!data || typeof data !== 'object') {
                this.importError = 'Invalid session data.';

                return;
            }
            const merged = { ...defaultState(), ...data };
            this.mode = merged.mode;
            this.shuffleMethod = merged.shuffleMethod;
            this.courtsCount = merged.courtsCount;
            this.timeLimitMinutes = merged.timeLimitMinutes;
            this.players = merged.players || [];
            this.queue = merged.queue || [];
            this.courts = merged.courts || [];
            this.completedMatches = merged.completedMatches || [];
            this.h2h = merged.h2h || {};
            if (clearShare) {
                this.shareUuid = '';
                this.shareSecret = '';
                this.shareSyncEnabled = false;
            }
            this.ensureCourtSlots();
            this.normalizePlayerCap();
            this.scoreDraft = {};
            this.uiPhase = 'session';
            this.persist();
        },

        /**
         * Keeps at most MAX_PLAYERS_PER_SESSION players; fixes queue and courts if roster shrinks.
         */
        normalizePlayerCap() {
            const max = MAX_PLAYERS_PER_SESSION;
            const before = this.players.length;
            if (before > max) {
                const kept = this.players.slice(0, max);
                const keepIds = new Set(kept.map((p) => String(p.id)));
                this.players = kept;
                this.queue = this.queue.filter((id) => keepIds.has(String(id)));
                this.courts = this.courts.map((c) => {
                    if (!c) {
                        return null;
                    }
                    const all = [...c.sideA, ...c.sideB];
                    if (all.some((id) => !keepIds.has(String(id)))) {
                        for (const x of all) {
                            if (keepIds.has(String(x)) && !this.queue.includes(x)) {
                                this.queue.push(x);
                            }
                        }
                        return null;
                    }
                    return c;
                });
                this.importError = `Only the first ${max} players are kept (session limit).`;
            }
        },

        playerCapReached() {
            return this.players.length >= MAX_PLAYERS_PER_SESSION;
        },

        sharePayload() {
            return {
                mode: this.mode,
                shuffleMethod: this.shuffleMethod,
                courtsCount: this.courtsCount,
                timeLimitMinutes: this.timeLimitMinutes,
                players: this.players,
                queue: this.queue,
                courts: this.courts,
                completedMatches: this.completedMatches,
                h2h: this.h2h,
            };
        },

        serialize() {
            return {
                ...this.sharePayload(),
                shareUuid: this.shareUuid,
                shareSecret: this.shareSecret,
                shareSyncEnabled: this.shareSyncEnabled,
                uiPhase: this.uiPhase,
                setupStep: this.setupStep,
            };
        },

        startOpenPlayWizard() {
            Object.assign(this, {
                ...defaultState(),
                historySessions: this.historySessions,
                historyQuota: this.historyQuota,
                historyError: this.historyError,
                historySaveTitle: this.historySaveTitle,
                importError: '',
                shareError: '',
                scoreDraft: {},
                uiPhase: 'setup',
                setupStep: 1,
                peopleModalOpen: false,
            });
            this.ensureCourtSlots();
            this.persist();
        },

        goToSessionList() {
            this.peopleModalOpen = false;
            this.uiPhase = 'list';
            this.setupStep = 1;
            this.refreshHistorySessions();
            this.persist();
        },

        setupGoBack() {
            if (this.setupStep > 1) {
                this.setupStep -= 1;
                this.persist();
                return;
            }
            this.goToSessionList();
        },

        setupGoNext() {
            if (this.setupStep < 3) {
                this.setupStep += 1;
                this.persist();
            }
        },

        finishSetup() {
            this.syncQueueFromIdle();
            this.uiPhase = 'session';
            this.setupStep = 1;
            this.peopleModalOpen = false;
            this.persist();
        },

        shuffleMethodLabel() {
            const m = {
                random: 'Random order',
                wins: 'Fewest wins first',
                levels: 'By skill level',
                teams: 'Fixed pairs (team codes)',
            };
            return m[this.shuffleMethod] || this.shuffleMethod;
        },

        scheduleSave() {
            clearTimeout(this.saveTimer);
            this.saveTimer = setTimeout(() => this.persist(), 200);
        },

        persist() {
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(this.serialize()));
            } catch {
                /* quota or private mode */
            }
            this.scheduleSharePush();
        },

        scheduleSharePush() {
            clearTimeout(this.shareSyncTimer);
            this.shareSyncTimer = setTimeout(() => {
                this.pushShareToServer();
            }, 500);
        },

        shareStoreUrl() {
            return this._gameqEndpoints().shareStore;
        },

        shareApiBase() {
            return this._gameqEndpoints().shareApiBase;
        },

        shareWatchUrl() {
            const base = this._gameqEndpoints().watchBase;
            if (!this.shareUuid || !base) {
                return '';
            }

            return `${String(base).replace(/\/$/, '')}/${this.shareUuid}`;
        },

        async pushShareToServer() {
            if (
                !this.shareSyncEnabled ||
                !this.shareUuid ||
                !this.shareSecret
            ) {
                return;
            }
            const root = this.shareApiBase();
            if (!root) {
                return;
            }
            this.shareError = '';
            try {
                const url = `${String(root).replace(/\/$/, '')}/${this.shareUuid}`;
                const r = await fetch(url, {
                    method: 'PUT',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': readCsrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        secret: this.shareSecret,
                        data: this.sharePayload(),
                    }),
                });
                const j = await r.json().catch(() => ({}));
                if (r.status === 403) {
                    this.shareError =
                        'Share key was rejected. Create a new link.';
                    this.shareSyncEnabled = false;
                    this.persist();
                    return;
                }
                if (!r.ok) {
                    this.shareError =
                        j.message || 'Could not sync (try again when online).';
                }
            } catch {
                this.shareError = 'Offline — will retry when you are back online.';
            }
        },

        async startSharing() {
            const storeUrl = this.shareStoreUrl();
            if (!storeUrl) {
                this.shareError = 'Share URL is not configured.';

                return;
            }
            this.shareBusy = true;
            this.shareError = '';
            try {
                const r = await fetch(storeUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': readCsrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ data: this.sharePayload() }),
                });
                const j = await r.json().catch(() => ({}));
                if (!r.ok) {
                    throw new Error(
                        j.message || 'Could not create share link.',
                    );
                }
                this.shareUuid = j.uuid;
                this.shareSecret = j.secret;
                this.shareSyncEnabled = true;
                this.persist();
                await this.pushShareToServer();
            } catch (e) {
                this.shareError =
                    e instanceof Error
                        ? e.message
                        : 'Could not create share link.';
            } finally {
                this.shareBusy = false;
            }
        },

        pauseSharing() {
            this.shareSyncEnabled = false;
            this.persist();
        },

        resumeSharing() {
            if (this.shareUuid && this.shareSecret) {
                this.shareSyncEnabled = true;
                this.persist();
            }
        },

        async revokeSharing() {
            if (
                !this.shareUuid ||
                !this.shareSecret ||
                !confirm('Stop sharing? People with the link will no longer see updates.')
            ) {
                return;
            }
            const root = this.shareApiBase();
            if (root) {
                try {
                    const url = `${String(root).replace(/\/$/, '')}/${this.shareUuid}`;
                    await fetch(url, {
                        method: 'DELETE',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': readCsrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ secret: this.shareSecret }),
                    });
                } catch {
                    /* still clear locally */
                }
            }
            this.shareUuid = '';
            this.shareSecret = '';
            this.shareSyncEnabled = false;
            this.shareError = '';
            this.persist();
        },

        async copyShareLink() {
            const url = this.shareWatchUrl();
            if (!url || !navigator.clipboard?.writeText) {
                return;
            }
            try {
                await navigator.clipboard.writeText(url);
                this.shareCopied = true;
                setTimeout(() => {
                    this.shareCopied = false;
                }, 2000);
            } catch {
                /* ignore */
            }
        },

        ensureCourtSlots() {
            const n = Math.max(1, Math.min(8, Number(this.courtsCount) || 1));
            this.courtsCount = n;
            while (this.courts.length < n) {
                this.courts.push(null);
            }
            if (this.courts.length > n) {
                this.courts = this.courts.slice(0, n);
            }
        },

        playerById(id) {
            return this.players.find((p) => p.id === id);
        },

        playerLabel(id) {
            const p = this.playerById(id);
            return p ? p.name : '?';
        },

        isOnCourt(playerId) {
            return this.courts.some(
                (c) => c && [...c.sideA, ...c.sideB].includes(playerId),
            );
        },

        eligiblePool() {
            return this.players.filter((p) => !p.disabled);
        },

        idleEligibleIds() {
            return this.eligiblePool()
                .map((p) => p.id)
                .filter((id) => !this.isOnCourt(id));
        },

        sortPlayersForMethod(players, method) {
            const arr = [...players];
            if (method === 'random') {
                return shuffleInPlace(arr);
            }
            if (method === 'wins') {
                arr.sort(
                    (a, b) =>
                        a.wins - b.wins ||
                        b.losses - a.losses ||
                        a.name.localeCompare(b.name),
                );
                return arr;
            }
            if (method === 'levels') {
                arr.sort(
                    (a, b) =>
                        a.level - b.level ||
                        a.name.localeCompare(b.name),
                );
                return arr;
            }
            if (method === 'teams') {
                arr.sort((a, b) => {
                    const ta = (a.teamId || '').trim();
                    const tb = (b.teamId || '').trim();
                    if (ta !== tb) {
                        return ta.localeCompare(tb);
                    }
                    return a.level - b.level || a.name.localeCompare(b.name);
                });
                return arr;
            }
            return arr;
        },

        buildSides(poolPlayers) {
            const method = this.shuffleMethod;
            if (this.mode === 'singles') {
                return this.sortPlayersForMethod(poolPlayers, method).map((p) => [p.id]);
            }
            if (method === 'teams') {
                const byTeam = {};
                for (const p of poolPlayers) {
                    const t = (p.teamId || '').trim() || `_none_${p.id}`;
                    if (!byTeam[t]) {
                        byTeam[t] = [];
                    }
                    byTeam[t].push(p);
                }
                const sides = [];
                for (const t of Object.keys(byTeam).sort()) {
                    const g = this.sortPlayersForMethod(byTeam[t], 'levels');
                    for (let i = 0; i + 1 < g.length; i += 2) {
                        sides.push([g[i].id, g[i + 1].id]);
                    }
                }
                return sides;
            }
            let arr = [...poolPlayers];
            if (method === 'random') {
                arr = shuffleInPlace(arr);
            } else if (method === 'wins') {
                arr.sort(
                    (a, b) =>
                        a.wins - b.wins ||
                        b.losses - a.losses ||
                        a.name.localeCompare(b.name),
                );
            } else if (method === 'levels') {
                arr.sort(
                    (a, b) =>
                        a.level - b.level ||
                        a.name.localeCompare(b.name),
                );
            }
            const sides = [];
            for (let i = 0; i + 1 < arr.length; i += 2) {
                sides.push([arr[i].id, arr[i + 1].id]);
            }
            return sides;
        },

        orderedPoolForFill() {
            const idle = this.idleEligibleIds();
            const inQueue = this.queue.filter((id) => idle.includes(id));
            const rest = idle.filter((id) => !this.queue.includes(id));
            const restPlayers = rest
                .map((id) => this.playerById(id))
                .filter(Boolean);
            const sortedRest = this.sortPlayersForMethod(
                restPlayers,
                this.shuffleMethod,
            ).map((p) => p.id);
            return [...inQueue, ...sortedRest]
                .map((id) => this.playerById(id))
                .filter(Boolean);
        },

        fillCourts() {
            this.ensureCourtSlots();
            const pool = this.orderedPoolForFill();
            const sides = this.buildSides(pool);
            const emptyIdx = [];
            for (let i = 0; i < this.courts.length; i++) {
                if (!this.courts[i]) {
                    emptyIdx.push(i);
                }
            }
            let si = 0;
            for (const idx of emptyIdx) {
                if (si + 1 >= sides.length) {
                    break;
                }
                const sideA = sides[si++];
                const sideB = sides[si++];
                this.courts[idx] = {
                    courtIndex: idx,
                    sideA,
                    sideB,
                    startedAt: Date.now(),
                };
            }
            this.syncQueueFromIdle();
            this.persist();
        },

        syncQueueFromIdle() {
            const onCourt = new Set();
            for (const c of this.courts) {
                if (c) {
                    for (const id of [...c.sideA, ...c.sideB]) {
                        onCourt.add(id);
                    }
                }
            }
            const shouldWait = this.eligiblePool()
                .map((p) => p.id)
                .filter((id) => !onCourt.has(id));
            const next = [];
            for (const id of this.queue) {
                if (shouldWait.includes(id)) {
                    next.push(id);
                }
            }
            for (const id of shouldWait) {
                if (!next.includes(id)) {
                    next.push(id);
                }
            }
            this.queue = next;
        },

        clearCourt(i) {
            const c = this.courts[i];
            if (!c) {
                return;
            }
            for (const id of [...c.sideA, ...c.sideB]) {
                if (!this.queue.includes(id)) {
                    this.queue.push(id);
                }
            }
            this.courts[i] = null;
            this.persist();
        },

        getScoreDraft(i) {
            if (!this.scoreDraft[i]) {
                this.scoreDraft[i] = { a: 0, b: 0 };
            }
            return this.scoreDraft[i];
        },

        completeMatch(i) {
            const court = this.courts[i];
            if (!court) {
                return;
            }
            const d = this.getScoreDraft(i);
            let scoreA = Number(d.a);
            let scoreB = Number(d.b);
            if (Number.isNaN(scoreA)) {
                scoreA = 0;
            }
            if (Number.isNaN(scoreB)) {
                scoreB = 0;
            }
            const winA = scoreA > scoreB;
            const winB = scoreB > scoreA;
            for (const id of court.sideA) {
                const p = this.playerById(id);
                if (p) {
                    if (winA) {
                        p.wins += 1;
                    } else if (winB) {
                        p.losses += 1;
                    }
                }
            }
            for (const id of court.sideB) {
                const p = this.playerById(id);
                if (p) {
                    if (winB) {
                        p.wins += 1;
                    } else if (winA) {
                        p.losses += 1;
                    }
                }
            }
            if (winA || winB) {
                this.bumpH2h(court.sideA, court.sideB, winA);
            }
            this.completedMatches.push({
                sideA: [...court.sideA],
                sideB: [...court.sideB],
                scoreA,
                scoreB,
                at: Date.now(),
                courtIndex: i,
            });
            this.courts[i] = null;
            for (const id of [...court.sideA, ...court.sideB]) {
                if (!this.queue.includes(id)) {
                    this.queue.push(id);
                }
            }
            this.scoreDraft[i] = { a: 0, b: 0 };
            this.persist();
        },

        bumpH2h(sideA, sideB, sideAWon) {
            const key = h2hStorageKey(sideA, sideB);
            const ka = sideKey(sideA);
            const kb = sideKey(sideB);
            const aIsLow = ka < kb;
            const row = this.h2h[key] || { winsLow: 0, winsHigh: 0 };
            if (sideAWon) {
                if (aIsLow) {
                    row.winsLow += 1;
                } else {
                    row.winsHigh += 1;
                }
            } else if (aIsLow) {
                row.winsHigh += 1;
            } else {
                row.winsLow += 1;
            }
            this.h2h[key] = row;
        },

        h2hRows() {
            return Object.entries(this.h2h).map(([key, row]) => {
                const [low, high] = key.split('||');
                const idsLow = low ? low.split(',') : [];
                const idsHigh = high ? high.split(',') : [];
                return {
                    key,
                    left: this.sideLabels(idsLow),
                    right: this.sideLabels(idsHigh),
                    winsLeft: row.winsLow,
                    winsRight: row.winsHigh,
                };
            });
        },

        rankings() {
            return [...this.players]
                .filter((p) => !p.disabled)
                .map((p) => {
                    const played = p.wins + p.losses;
                    const pct = played ? Math.round((100 * p.wins) / played) : 0;
                    return { ...p, played, pct };
                })
                .sort(
                    (a, b) =>
                        b.wins - a.wins ||
                        b.pct - a.pct ||
                        a.name.localeCompare(b.name),
                );
        },

        addPlayer() {
            if (this.players.length >= MAX_PLAYERS_PER_SESSION) {
                return;
            }
            const name = (this.newName || '').trim();
            if (!name) {
                return;
            }
            this.players.push({
                id: newId(),
                name,
                level: Math.max(1, Math.min(10, Number(this.newLevel) || 3)),
                wins: 0,
                losses: 0,
                disabled: false,
                teamId: (this.newTeamId || '').trim(),
            });
            this.newName = '';
            this.newTeamId = '';
            this.persist();
        },

        removePlayer(id) {
            this.players = this.players.filter((p) => p.id !== id);
            this.queue = this.queue.filter((x) => x !== id);
            this.courts = this.courts.map((c) => {
                if (!c) {
                    return null;
                }
                const sideA = c.sideA.filter((x) => x !== id);
                const sideB = c.sideB.filter((x) => x !== id);
                if (
                    sideA.length !== c.sideA.length ||
                    sideB.length !== c.sideB.length
                ) {
                    for (const x of [...c.sideA, ...c.sideB]) {
                        if (x !== id && !this.queue.includes(x)) {
                            this.queue.push(x);
                        }
                    }
                    return null;
                }
                return c;
            });
            this.persist();
        },

        toggleDisabled(id) {
            const p = this.playerById(id);
            if (p) {
                p.disabled = !p.disabled;
                if (p.disabled) {
                    this.queue = this.queue.filter((x) => x !== id);
                    this.courts = this.courts.map((c) => {
                        if (!c) {
                            return null;
                        }
                        if (
                            [...c.sideA, ...c.sideB].includes(id)
                        ) {
                            for (const x of [...c.sideA, ...c.sideB]) {
                                if (!this.queue.includes(x)) {
                                    this.queue.push(x);
                                }
                            }
                            return null;
                        }
                        return c;
                    });
                }
            }
            this.persist();
        },

        moveQueueUp(i) {
            if (i <= 0) {
                return;
            }
            const q = [...this.queue];
            [q[i - 1], q[i]] = [q[i], q[i - 1]];
            this.queue = q;
            this.persist();
        },

        moveQueueDown(i) {
            if (i >= this.queue.length - 1) {
                return;
            }
            const q = [...this.queue];
            [q[i], q[i + 1]] = [q[i + 1], q[i]];
            this.queue = q;
            this.persist();
        },

        removeFromQueue(i) {
            this.queue.splice(i, 1);
            this.persist();
        },

        resetSession() {
            if (
                !confirm(
                    'Clear all scores, matches, and head-to-head? Your player list stays.',
                )
            ) {
                return;
            }
            for (const p of this.players) {
                p.wins = 0;
                p.losses = 0;
            }
            this.queue = [];
            this.courts = this.courts.map(() => null);
            this.completedMatches = [];
            this.h2h = {};
            this.scoreDraft = {};
            this.persist();
        },

        fullReset() {
            if (
                !confirm(
                    'Delete all players and session data? This cannot be undone.',
                )
            ) {
                return;
            }
            localStorage.removeItem(STORAGE_KEY);
            Object.assign(this, defaultState());
            this.ensureCourtSlots();
            this.scoreDraft = {};
            this.shareError = '';
            this.uiPhase = 'list';
            this.setupStep = 1;
            this.peopleModalOpen = false;
            this.persist();
        },

        exportJson() {
            const blob = new Blob([JSON.stringify(this.sharePayload(), null, 2)], {
                type: 'application/json',
            });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = `gameq-${new Date().toISOString().slice(0, 10)}.json`;
            a.click();
            URL.revokeObjectURL(a.href);
        },

        importJson(event) {
            this.importError = '';
            const file = event.target.files?.[0];
            if (!file) {
                return;
            }
            const reader = new FileReader();
            reader.onload = () => {
                try {
                    const data = JSON.parse(String(reader.result));
                    this.applyImportedPayload(data, { clearShare: true });
                    this.importError = '';
                } catch {
                    this.importError = 'Invalid JSON file.';
                }
            };
            reader.readAsText(file);
            event.target.value = '';
        },

        remainingSeconds(court) {
            this.tick;
            const min = Number(this.timeLimitMinutes) || 0;
            if (!min || !court?.startedAt) {
                return null;
            }
            const end = court.startedAt + min * 60 * 1000;
            return Math.max(0, Math.floor((end - Date.now()) / 1000));
        },

        formatCountdown(sec) {
            if (sec == null) {
                return '';
            }
            const m = Math.floor(sec / 60);
            const s = sec % 60;
            return `${m}:${String(s).padStart(2, '0')}`;
        },

        courtsCountChanged() {
            this.ensureCourtSlots();
            this.persist();
        },

        sideLabels(ids) {
            return ids.map((id) => this.playerLabel(id)).join(' · ');
        },
    }));
});
