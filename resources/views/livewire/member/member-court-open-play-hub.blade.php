@php
    use App\Models\Booking;
    use App\Models\OpenPlayParticipant;

    $tz = config('app.timezone', 'UTC');
@endphp

<div class="space-y-8">
    <div>
        <h1 class="font-display text-2xl font-bold text-zinc-900 dark:text-white">Court open play</h1>
        <p class="mt-1 max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
            Host sessions you’ve created, or browse open plays you’ve joined — with slot counts and details in one place.
        </p>
    </div>

    @if (session('status'))
        <div
            class="rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-medium text-teal-950 dark:border-teal-900/50 dark:bg-teal-950/40 dark:text-teal-100"
            role="status"
        >
            {{ session('status') }}
        </div>
    @endif

    <section class="rounded-2xl border border-emerald-200/80 bg-white p-6 shadow-sm dark:border-emerald-900/40 dark:bg-zinc-900/80">
        <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">You’re joining</h2>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            Open plays you requested or got into — joiner slots (not including the host)
        </p>
        <ul class="mt-5 space-y-4">
            @forelse ($joinedRows as $row)
                @if ($row->booking)
                    @php
                        $b = $row->booking;
                        $max = $b->open_play_max_slots;
                        $filled = (int) ($b->accepted_joiners_count ?? $b->acceptedOpenPlayParticipantsCount());
                        $remaining = $b->openPlaySlotsRemaining();
                    @endphp
                    <li
                        class="rounded-xl border border-emerald-100 bg-emerald-50/40 px-4 py-4 dark:border-emerald-900/30 dark:bg-emerald-950/15"
                        wire:key="joined-{{ $row->id }}"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ $b->courtClient?->name ?? 'Venue' }}
                                    · {{ $b->court?->name ?? 'Court' }}
                                </p>
                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $b->starts_at?->timezone($tz)->format('l, M j, Y') }}
                                    ·
                                    {{ $b->starts_at?->timezone($tz)->format('g:i A') }}
                                    –
                                    {{ $b->ends_at?->timezone($tz)->format('g:i A') }}
                                </p>
                                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-500">
                                    Host: <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $b->user?->name ?? '—' }}</span>
                                    · Booking {{ Booking::statusDisplayLabel($b->status) }}
                                    · Your request:
                                    <span class="font-semibold text-emerald-800 dark:text-emerald-200">
                                        @if ($row->status === OpenPlayParticipant::STATUS_ACCEPTED)
                                            Accepted
                                        @elseif ($row->status === OpenPlayParticipant::STATUS_WAITING_LIST)
                                            Waiting list
                                        @else
                                            Pending
                                        @endif
                                    </span>
                                </p>
                                <p class="mt-2 text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                    Slots (joiners):
                                    <span class="tabular-nums">{{ $filled }}</span>
                                    /
                                    <span class="tabular-nums">{{ $max ?? '—' }}</span>
                                    filled
                                    @if ($max !== null)
                                        ·
                                        <span class="text-emerald-700 dark:text-emerald-300">{{ $remaining }} left</span>
                                    @endif
                                </p>
                                @if ($b->open_play_public_notes)
                                    <div class="mt-3 rounded-lg border border-emerald-200/60 bg-white/80 px-3 py-2 text-sm text-zinc-700 dark:border-emerald-900/40 dark:bg-zinc-950/50 dark:text-zinc-300">
                                        <span class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                            Open play notes
                                        </span>
                                        <p class="mt-1 whitespace-pre-wrap">{{ $b->open_play_public_notes }}</p>
                                    </div>
                                @endif
                                @if ($b->open_play_refund_policy)
                                    <div class="mt-3 rounded-lg border border-amber-200/70 bg-amber-50/80 px-3 py-2 text-sm text-amber-950 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-100">
                                        <span class="text-xs font-bold uppercase tracking-wider text-amber-800 dark:text-amber-300">
                                            Refund policy
                                        </span>
                                        <p class="mt-1 whitespace-pre-wrap">{{ $b->open_play_refund_policy }}</p>
                                    </div>
                                @endif
                                @if ($row->status === OpenPlayParticipant::STATUS_ACCEPTED && $b->open_play_host_payment_details)
                                    <div class="mt-3 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950/80">
                                        <span class="text-xs font-bold uppercase tracking-wider text-zinc-500">Payment (host)</span>
                                        <p class="mt-1 whitespace-pre-wrap text-zinc-800 dark:text-zinc-200">
                                            {{ $b->open_play_host_payment_details }}
                                        </p>
                                    </div>
                                @endif
                                @if ($b->open_play_external_contact)
                                    <div class="mt-3 rounded-lg border border-violet-200/80 bg-violet-50/60 px-3 py-2 text-sm dark:border-violet-900/40 dark:bg-violet-950/25">
                                        <span class="text-xs font-bold uppercase tracking-wider text-violet-700 dark:text-violet-300">
                                            Refund / contact
                                        </span>
                                        <p class="mt-1 whitespace-pre-wrap text-violet-950 dark:text-violet-100">
                                            {{ $b->open_play_external_contact }}
                                        </p>
                                    </div>
                                @endif
                                @if ($row->gcash_reference)
                                    <p class="mt-2 font-mono text-xs text-zinc-600 dark:text-zinc-400">
                                        Your GCash ref:
                                        <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $row->gcash_reference }}</span>
                                    </p>
                                @endif
                            </div>
                            <div class="flex shrink-0 flex-col gap-2 sm:items-end">
                                <a
                                    href="{{ route('account.court-open-plays.join', $b) }}"
                                    wire:navigate
                                    class="rounded-lg bg-emerald-600 px-3 py-2 text-center text-xs font-bold uppercase tracking-wide text-white hover:bg-emerald-500"
                                >
                                    Details &amp; ref
                                </a>
                            </div>
                        </div>
                    </li>
                @endif
            @empty
                <li class="rounded-xl border border-dashed border-zinc-200 py-10 text-center dark:border-zinc-700">
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">You’re not in any upcoming open plays yet.</p>
                    <p class="mt-1 text-xs text-zinc-500">
                        Ask a host for their join link, or find one from friends.
                    </p>
                </li>
            @endforelse
        </ul>
    </section>

    <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80">
        <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">You’re hosting</h2>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Upcoming open-play bookings you created</p>
        <ul class="mt-5 space-y-3">
            @forelse ($hostedSessions as $b)
                <li
                    class="flex flex-col gap-3 rounded-xl border border-zinc-100 bg-zinc-50/80 px-4 py-4 dark:border-zinc-800 dark:bg-zinc-950/50 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <p class="font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $b->courtClient?->name ?? 'Venue' }}
                            · {{ $b->court?->name ?? 'Court' }}
                        </p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $b->starts_at?->timezone($tz)->format('D, M j · g:i A') }}
                            · {{ Booking::statusDisplayLabel($b->status) }}
                        </p>
                        <p class="mt-1 text-xs text-zinc-500">
                            Pending {{ $b->pending_participants_count }} · In
                            {{ $b->accepted_participants_count }}/{{ $b->open_play_max_slots ?? '—' }} slots
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a
                            href="{{ route('account.court-open-plays.join', $b) }}"
                            wire:navigate
                            class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs font-bold uppercase tracking-wide text-zinc-800 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
                        >
                            Join link
                        </a>
                        <a
                            href="{{ route('account.court-open-plays.host', $b) }}"
                            wire:navigate
                            class="rounded-lg bg-violet-600 px-3 py-2 text-xs font-bold uppercase tracking-wide text-white hover:bg-violet-500"
                        >
                            Manage
                        </a>
                    </div>
                </li>
            @empty
                <li class="rounded-xl border border-dashed border-zinc-200 py-10 text-center dark:border-zinc-700">
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">No open-play sessions yet.</p>
                    <p class="mt-1 text-xs text-zinc-500">
                        Book a court (single block) and turn on <strong>Open play</strong> at checkout.
                    </p>
                </li>
            @endforelse
        </ul>
    </section>
</div>
