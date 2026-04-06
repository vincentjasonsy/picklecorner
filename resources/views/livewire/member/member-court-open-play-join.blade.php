@php
    use App\Models\Booking;
    use App\Models\OpenPlayParticipant;

    $tz = config('app.timezone', 'UTC');
@endphp

<div class="mx-auto max-w-2xl space-y-8">
    <div>
        <h1 class="font-display text-2xl font-bold text-zinc-900 dark:text-white">Open play</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
            {{ $booking->courtClient?->name }} · {{ $booking->court?->name }}
        </p>
        <p class="mt-2 text-sm font-medium text-zinc-800 dark:text-zinc-200">
            {{ $booking->starts_at?->timezone($tz)->format('l, M j, Y') }}
            · {{ $booking->starts_at?->timezone($tz)->format('g:i A') }}
            –
            {{ $booking->ends_at?->timezone($tz)->format('g:i A') }}
        </p>
        <p class="mt-2 text-xs text-zinc-500">
            Host: {{ $booking->user?->name ?? 'Member' }} · Booking
            {{ Booking::statusDisplayLabel($booking->status) }}
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

    @if ($isHost)
        <div
            class="rounded-xl border border-violet-200 bg-violet-50 px-4 py-4 text-sm text-violet-950 dark:border-violet-900/50 dark:bg-violet-950/30 dark:text-violet-100"
        >
            You’re the host for this booking.
            <a href="{{ route('account.court-open-plays.host', $booking) }}" wire:navigate class="font-bold underline">Manage
                requests</a>
            or
            <a href="{{ route('account.court-open-plays.index') }}" wire:navigate class="font-bold underline">back to hub</a>.
        </div>
    @else
        @if ($booking->status !== Booking::STATUS_CONFIRMED)
            <div
                class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100"
            >
                The venue hasn’t confirmed this booking yet. You can read the details below, but join requests open only
                after the booking is confirmed.
            </div>
        @endif

        @if ($booking->open_play_public_notes)
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80">
                <h2 class="font-display text-sm font-bold text-zinc-900 dark:text-white">Notes from the host</h2>
                <p class="mt-2 whitespace-pre-wrap text-sm text-zinc-700 dark:text-zinc-300">{{ $booking->open_play_public_notes }}</p>
            </section>
        @endif

        @if ($booking->open_play_host_payment_details)
            <section class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-6 dark:border-emerald-900/40 dark:bg-emerald-950/20">
                <h2 class="font-display text-sm font-bold text-emerald-900 dark:text-emerald-100">Payment (from host)</h2>
                <p class="mt-2 whitespace-pre-wrap text-sm text-emerald-950 dark:text-emerald-100">
                    {{ $booking->open_play_host_payment_details }}
                </p>
            </section>
        @endif

        @if ($booking->open_play_external_contact)
            <section class="rounded-2xl border border-violet-200/80 bg-violet-50/50 p-6 dark:border-violet-900/40 dark:bg-violet-950/20">
                <h2 class="font-display text-sm font-bold text-violet-950 dark:text-violet-100">
                    Refund / contact (from host)
                </h2>
                <p class="mt-2 whitespace-pre-wrap text-sm text-violet-950 dark:text-violet-100">
                    {{ $booking->open_play_external_contact }}
                </p>
            </section>
        @endif

        @if ($booking->open_play_refund_policy)
            <section class="rounded-2xl border border-amber-200/80 bg-amber-50/40 p-6 dark:border-amber-900/35 dark:bg-amber-950/20">
                <h2 class="font-display text-sm font-bold text-amber-950 dark:text-amber-100">Refund policy (from host)</h2>
                <p class="mt-2 whitespace-pre-wrap text-sm text-amber-950 dark:text-amber-100">
                    {{ $booking->open_play_refund_policy }}
                </p>
            </section>
        @endif

        @if ($myParticipant && in_array($myParticipant->status, [OpenPlayParticipant::STATUS_REJECTED, OpenPlayParticipant::STATUS_REMOVED_BY_HOST], true))
            <div
                class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100"
                role="status"
            >
                <p class="font-bold">
                    {{ $myParticipant->status === OpenPlayParticipant::STATUS_REJECTED ? 'Request not accepted' : 'Removed from this open play' }}
                </p>
                @if ($myParticipant->host_closure_reason)
                    <p class="mt-2 font-medium">
                        {{ OpenPlayParticipant::hostClosureReasonLabel($myParticipant->host_closure_reason) }}
                    </p>
                @endif
                @if ($myParticipant->host_closure_message)
                    <p class="mt-2 whitespace-pre-wrap text-amber-900/90 dark:text-amber-100/90">
                        {{ $myParticipant->host_closure_message }}
                    </p>
                @endif
                @if (! $booking->open_play_external_contact)
                    <p class="mt-3 text-xs text-amber-800/80 dark:text-amber-200/80">
                        The host may add a contact line on Manage open play for refunds or follow-up.
                    </p>
                @endif
            </div>
        @endif

        @php
            $joinFormEligible =
                $myParticipant === null ||
                in_array($myParticipant->status, [
                    OpenPlayParticipant::STATUS_REJECTED,
                    OpenPlayParticipant::STATUS_CANCELLED,
                    OpenPlayParticipant::STATUS_REMOVED_BY_HOST,
                ], true);
        @endphp

        @if ($myParticipant)
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                    @php
                        $statusLabel = match ($myParticipant->status) {
                            OpenPlayParticipant::STATUS_PENDING => 'Pending',
                            OpenPlayParticipant::STATUS_WAITING_LIST => 'Waiting list',
                            OpenPlayParticipant::STATUS_ACCEPTED => 'Accepted',
                            OpenPlayParticipant::STATUS_REJECTED => 'Declined',
                            OpenPlayParticipant::STATUS_REMOVED_BY_HOST => 'Removed by host',
                            OpenPlayParticipant::STATUS_CANCELLED => 'You left',
                            default => ucfirst($myParticipant->status),
                        };
                    @endphp
                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">Your status: {{ $statusLabel }}</p>
                    @if ($myParticipant->status === OpenPlayParticipant::STATUS_PENDING)
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Waiting for the host to respond.</p>
                    @endif
                    @if ($myParticipant->status === OpenPlayParticipant::STATUS_WAITING_LIST)
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                            Joiner slots are full — you’re queued. The host can accept you if a spot opens.
                        </p>
                    @endif
                    @if (in_array($myParticipant->status, [OpenPlayParticipant::STATUS_PENDING, OpenPlayParticipant::STATUS_ACCEPTED, OpenPlayParticipant::STATUS_WAITING_LIST], true))
                        <div class="mt-4 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                            <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                GCash transaction ID
                            </label>
                            <p class="mt-0.5 text-xs text-zinc-500">
                                Host uses this to match your payment. Update anytime before game day.
                            </p>
                            <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-end">
                                <input
                                    type="text"
                                    wire:model="gcashReference"
                                    class="min-w-0 flex-1 rounded-lg border border-zinc-200 px-3 py-2 font-mono text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                    placeholder="Transaction reference"
                                    autocomplete="off"
                                />
                                <button
                                    type="button"
                                    wire:click="updateGcashReference"
                                    class="shrink-0 rounded-lg bg-zinc-800 px-3 py-2 text-xs font-bold uppercase tracking-wide text-white dark:bg-zinc-200 dark:text-zinc-900"
                                >
                                    Save ref
                                </button>
                            </div>
                            @error('gcashReference')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <button
                            type="button"
                            wire:click="leaveOpenPlay"
                            wire:confirm="Leave this open play?"
                            class="mt-4 text-sm font-bold text-red-600 hover:text-red-700 dark:text-red-400"
                        >
                            Leave open play
                        </button>
                    @endif
                </div>
            @endif

            @if ($joinFormEligible)
                @if ($booking->allowsOpenPlayJoinRequests())
                    <form wire:submit="requestJoin" class="space-y-4 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Note to host (optional)
                            </label>
                            <textarea
                                wire:model="joinerNote"
                                rows="2"
                                class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                placeholder="Skill level, partner preference…"
                            ></textarea>
                            @error('joinerNote')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                GCash ref (optional)
                            </label>
                            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-500">
                                If you’ve already sent payment to the host, add the transaction ID so they can confirm.
                            </p>
                            <input
                                type="text"
                                wire:model="gcashReference"
                                class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 font-mono text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                placeholder="e.g. 012345678901"
                                autocomplete="off"
                            />
                            @error('gcashReference')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        @error('join')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <button
                            type="submit"
                            class="rounded-xl bg-violet-600 px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-violet-500"
                        >
                            {{ $myParticipant ? 'Request again' : 'Request to join' }}
                        </button>
                    </form>
                @elseif ($booking->allowsOpenPlayWaitlistRequests())
                    <div
                        class="space-y-4 rounded-xl border border-amber-200/80 bg-amber-50/50 p-6 dark:border-amber-900/40 dark:bg-amber-950/20"
                    >
                        <p class="text-sm font-medium text-amber-950 dark:text-amber-100">
                            Joiner slots are full, but you can join the <strong>waiting list</strong>. If someone drops or
                            the host opens a spot, they may accept you in order.
                        </p>
                        <form wire:submit="requestJoin" class="space-y-4">
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    Note to host (optional)
                                </label>
                                <textarea
                                    wire:model="joinerNote"
                                    rows="2"
                                    class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                    placeholder="Skill level, partner preference…"
                                ></textarea>
                                @error('joinerNote')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    GCash ref (optional)
                                </label>
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-500">
                                    Optional — helps the host match payment if they offer you a spot later.
                                </p>
                                <input
                                    type="text"
                                    wire:model="gcashReference"
                                    class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 font-mono text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                    placeholder="e.g. 012345678901"
                                    autocomplete="off"
                                />
                                @error('gcashReference')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            @error('join')
                                <p class="text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <button
                                type="submit"
                                class="rounded-xl bg-amber-700 px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-amber-600 dark:bg-amber-600 dark:hover:bg-amber-500"
                            >
                                {{ $myParticipant ? 'Request again (waiting list)' : 'Join waiting list' }}
                            </button>
                        </form>
                    </div>
                @else
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        @if ($booking->starts_at && $booking->starts_at->lte(now()))
                            This session has already started or ended.
                        @else
                            No spots available right now.
                        @endif
                    </p>
                @endif
            @endif
    @endif
</div>
