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

        @if ($myParticipant && $myParticipant->status === OpenPlayParticipant::STATUS_ACCEPTED && $booking->open_play_host_payment_details)
            <section class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-6 dark:border-emerald-900/40 dark:bg-emerald-950/20">
                <h2 class="font-display text-sm font-bold text-emerald-900 dark:text-emerald-100">Payment (from host)</h2>
                <p class="mt-2 whitespace-pre-wrap text-sm text-emerald-950 dark:text-emerald-100">
                    {{ $booking->open_play_host_payment_details }}
                </p>
            </section>
        @endif

        @if ($myParticipant)
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">Your status: {{ ucfirst($myParticipant->status) }}</p>
                    @if ($myParticipant->status === OpenPlayParticipant::STATUS_PENDING)
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Waiting for the host to respond.</p>
                    @endif
                    @if (in_array($myParticipant->status, [OpenPlayParticipant::STATUS_PENDING, OpenPlayParticipant::STATUS_ACCEPTED], true))
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
            @else
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
                            Request to join
                        </button>
                    </form>
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
