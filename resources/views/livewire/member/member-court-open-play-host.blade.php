@php
    use App\Models\Booking;
    use App\Models\OpenPlayParticipant;

    $tz = config('app.timezone', 'UTC');
@endphp

<div class="space-y-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Host</p>
            <h1 class="mt-1 font-display text-2xl font-bold text-zinc-900 dark:text-white">Manage open play</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                {{ $booking->courtClient?->name }} · {{ $booking->court?->name }}
                · {{ $booking->starts_at?->timezone($tz)->format('D, M j · g:i A') }}
            </p>
            <p class="mt-2 text-xs text-zinc-500">
                Booking status: <strong>{{ Booking::statusDisplayLabel($booking->status) }}</strong>
                · {{ $booking->acceptedOpenPlayParticipantsCount() }}/{{ $booking->open_play_max_slots }} joiners
                @if ($booking->openPlaySlotsRemaining() > 0)
                    · {{ $booking->openPlaySlotsRemaining() }} slot(s) left
                @else
                    · <span class="text-amber-700 dark:text-amber-300">Full</span>
                @endif
            </p>
        </div>
        <a
            href="{{ route('account.court-open-plays.index') }}"
            wire:navigate
            class="text-sm font-semibold text-violet-600 hover:text-violet-700 dark:text-violet-400"
        >
            ← Back to hub
        </a>
    </div>

    @if (session('status'))
        <div
            class="rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-medium text-teal-950 dark:border-teal-900/50 dark:bg-teal-950/40 dark:text-teal-100"
            role="status"
        >
            {{ session('status') }}
        </div>
    @endif

    <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80">
        <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Share link</h2>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Send this to players — they’ll sign in to request a spot.</p>
        <div class="mt-3 flex flex-wrap items-center gap-2">
            <code
                class="max-w-full break-all rounded-lg bg-zinc-100 px-3 py-2 text-xs text-zinc-800 dark:bg-zinc-950 dark:text-zinc-200"
            >{{ $joinUrl }}</code>
            <button
                type="button"
                x-data
                x-on:click="navigator.clipboard.writeText(@js($joinUrl))"
                class="rounded-lg bg-zinc-800 px-3 py-2 text-xs font-bold uppercase tracking-wide text-white dark:bg-zinc-200 dark:text-zinc-900"
            >
                Copy
            </button>
        </div>
    </section>

    @if ($booking->open_play_public_notes)
        <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80">
            <h2 class="font-display text-sm font-bold text-zinc-900 dark:text-white">Your notes (public)</h2>
            <p class="mt-2 whitespace-pre-wrap text-sm text-zinc-700 dark:text-zinc-300">{{ $booking->open_play_public_notes }}</p>
        </section>
    @endif

    @if ($booking->open_play_host_payment_details)
        <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80">
            <h2 class="font-display text-sm font-bold text-zinc-900 dark:text-white">Payment details (shown to accepted players)</h2>
            <p class="mt-2 whitespace-pre-wrap text-sm text-zinc-700 dark:text-zinc-300">{{ $booking->open_play_host_payment_details }}</p>
        </section>
    @endif

    <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80">
        <h2 class="font-display text-sm font-bold text-zinc-900 dark:text-white">Refund / contact for players</h2>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            Optional — shown to joiners so they can coordinate refunds or reach you outside the app.
        </p>
        <form wire:submit="saveExternalContact" class="mt-4 space-y-3">
            <textarea
                wire:model="externalContactDraft"
                rows="3"
                class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                placeholder="Viber · email · phone — how to reach you for refunds"
            ></textarea>
            @error('externalContactDraft')
                <p class="text-xs text-red-600">{{ $message }}</p>
            @enderror
            <button
                type="submit"
                class="rounded-lg bg-zinc-800 px-4 py-2 text-xs font-bold uppercase tracking-wide text-white dark:bg-zinc-200 dark:text-zinc-900"
            >
                Save contact line
            </button>
        </form>
        <form wire:submit="saveRefundPolicy" class="mt-8 space-y-3 border-t border-zinc-100 pt-6 dark:border-zinc-800">
            <h3 class="font-display text-sm font-bold text-zinc-900 dark:text-white">Refund policy (public)</h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Optional — e.g. partial refunds only, or that you’re not obliged to refund the full amount. Shown on the
                join page with your open play details.
            </p>
            <textarea
                wire:model="refundPolicyDraft"
                rows="4"
                class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                placeholder="e.g. Cancellations: up to 50% refund; host may decline full refunds."
            ></textarea>
            @error('refundPolicyDraft')
                <p class="text-xs text-red-600">{{ $message }}</p>
            @enderror
            <button
                type="submit"
                class="rounded-lg border border-zinc-200 bg-white px-4 py-2 text-xs font-bold uppercase tracking-wide text-zinc-800 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-200"
            >
                Save refund policy
            </button>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80">
        <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-800">
            <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Players</h2>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                Waiting list = interested after joiner slots are full — accept when a spot opens.
            </p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50 dark:bg-zinc-800/40">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-300">Name</th>
                        <th class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-300">Status</th>
                        <th class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-300">Note</th>
                        <th class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-300">GCash ref</th>
                        <th class="px-4 py-3 text-right font-semibold text-zinc-700 dark:text-zinc-300">Paid</th>
                        <th class="px-4 py-3 text-right font-semibold text-zinc-700 dark:text-zinc-300"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($participants as $p)
                        <tr wire:key="opp-{{ $p->id }}">
                            <td class="px-4 py-3">
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $p->user?->name ?? '—' }}</span>
                                @if ($p->user?->email)
                                    <span class="block text-xs text-zinc-500">{{ $p->user->email }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                @php
                                    $statusLabel = match ($p->status) {
                                        OpenPlayParticipant::STATUS_PENDING => 'Pending',
                                        OpenPlayParticipant::STATUS_WAITING_LIST => 'Waiting list',
                                        OpenPlayParticipant::STATUS_ACCEPTED => 'Accepted',
                                        OpenPlayParticipant::STATUS_REJECTED => 'Declined',
                                        OpenPlayParticipant::STATUS_REMOVED_BY_HOST => 'Removed',
                                        OpenPlayParticipant::STATUS_CANCELLED => 'Cancelled',
                                        default => ucfirst($p->status),
                                    };
                                @endphp
                                <span>{{ $statusLabel }}</span>
                                @if ($p->host_closure_reason)
                                    <span class="mt-0.5 block text-xs text-zinc-500">
                                        {{ OpenPlayParticipant::hostClosureReasonLabel($p->host_closure_reason) }}
                                    </span>
                                @endif
                            </td>
                            <td class="max-w-[12rem] truncate px-4 py-3 text-xs text-zinc-500">{{ $p->joiner_note ?? '—' }}</td>
                            <td class="max-w-[10rem] px-4 py-3 font-mono text-xs text-zinc-700 dark:text-zinc-300">
                                {{ $p->gcash_reference ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-right text-zinc-600 dark:text-zinc-400">
                                @if ($p->status === OpenPlayParticipant::STATUS_ACCEPTED)
                                    {{ $p->paid_at ? $p->paid_at->timezone($tz)->format('M j g:i A') : '—' }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    @if (in_array($p->status, [OpenPlayParticipant::STATUS_PENDING, OpenPlayParticipant::STATUS_WAITING_LIST], true))
                                        <button
                                            type="button"
                                            wire:click="acceptParticipant('{{ $p->id }}')"
                                            class="text-xs font-bold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                                        >
                                            Accept
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="openClosureModal('{{ $p->id }}', 'reject')"
                                            class="text-xs font-bold text-zinc-600 hover:text-zinc-800 dark:text-zinc-400"
                                        >
                                            Decline
                                        </button>
                                    @endif
                                    @if ($p->status === OpenPlayParticipant::STATUS_ACCEPTED)
                                        <button
                                            type="button"
                                            wire:click="toggleParticipantPaid('{{ $p->id }}')"
                                            class="text-xs font-bold text-violet-600 hover:text-violet-700 dark:text-violet-400"
                                        >
                                            {{ $p->paid_at ? 'Unmark paid' : 'Mark paid' }}
                                        </button>
                                    @endif
                                    @if (in_array($p->status, [OpenPlayParticipant::STATUS_PENDING, OpenPlayParticipant::STATUS_ACCEPTED], true))
                                        <button
                                            type="button"
                                            wire:click="openClosureModal('{{ $p->id }}', 'remove')"
                                            class="text-xs font-bold text-red-600 hover:text-red-700 dark:text-red-400"
                                        >
                                            Remove
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-zinc-500">No requests yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($closureParticipantId)
        <div
            class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-4 sm:items-center"
            wire:click.self="closeClosureModal"
        >
            <div
                class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-700 dark:bg-zinc-900"
                wire:click.stop
            >
                <h3 class="font-display text-lg font-bold text-zinc-900 dark:text-white">
                    {{ $closureAction === 'reject' ? 'Decline request' : 'Remove player' }}
                </h3>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    Players see this reason on the join page. Choose the option that fits best.
                </p>
                <div class="mt-4 space-y-3">
                    <fieldset>
                        <legend class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Reason
                        </legend>
                        <div class="mt-2 space-y-2">
                            <label class="flex cursor-pointer items-start gap-2 text-sm">
                                <input
                                    type="radio"
                                    wire:model.live="closureReason"
                                    value="{{ OpenPlayParticipant::CLOSURE_WRONG_TRANSACTION }}"
                                    class="mt-1"
                                />
                                <span>
                                    {{ OpenPlayParticipant::hostClosureReasonLabel(OpenPlayParticipant::CLOSURE_WRONG_TRANSACTION) }}
                                </span>
                            </label>
                            <label class="flex cursor-pointer items-start gap-2 text-sm">
                                <input
                                    type="radio"
                                    wire:model.live="closureReason"
                                    value="{{ OpenPlayParticipant::CLOSURE_FULL_SLOTS }}"
                                    class="mt-1"
                                />
                                <span>
                                    {{ OpenPlayParticipant::hostClosureReasonLabel(OpenPlayParticipant::CLOSURE_FULL_SLOTS) }}
                                </span>
                            </label>
                            <label class="flex cursor-pointer items-start gap-2 text-sm">
                                <input
                                    type="radio"
                                    wire:model.live="closureReason"
                                    value="{{ OpenPlayParticipant::CLOSURE_OTHER }}"
                                    class="mt-1"
                                />
                                <span> Other — add a short note below </span>
                            </label>
                        </div>
                    </fieldset>
                    @error('closureReason')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Note <span class="font-normal normal-case text-zinc-400">(optional, or required for Other)</span>
                        </label>
                        <textarea
                            wire:model="closureMessage"
                            rows="3"
                            class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                            placeholder="Extra context for refunds or coordination…"
                        ></textarea>
                        @error('closureMessage')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="mt-6 flex flex-wrap justify-end gap-2">
                    <button
                        type="button"
                        wire:click="closeClosureModal"
                        class="rounded-lg border border-zinc-200 px-4 py-2 text-xs font-bold uppercase tracking-wide text-zinc-700 dark:border-zinc-600 dark:text-zinc-300"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        wire:click="submitClosure"
                        class="rounded-lg bg-red-600 px-4 py-2 text-xs font-bold uppercase tracking-wide text-white hover:bg-red-500"
                    >
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
