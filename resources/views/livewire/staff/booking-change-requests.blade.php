@php
    use App\Models\Booking;
    use App\Models\BookingChangeRequest;
    use App\Support\Money;
@endphp

<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <h1 class="font-display text-2xl font-extrabold text-zinc-900 dark:text-white">Refund &amp; reschedule</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
            Members request credit refunds or new times; accepting a refund cancels the booking and adds venue credit
            to their account.
        </p>
    </div>

    @if ($this->pendingChangeRequests->isEmpty())
        <p
            class="rounded-xl border border-dashed border-zinc-300 px-6 py-10 text-center text-sm text-zinc-500 dark:border-zinc-600"
        >
            No pending requests.
        </p>
    @else
        <ul class="space-y-4">
            @foreach ($this->pendingChangeRequests as $req)
                @php
                    $b = $req->booking;
                    $cur = $b?->currency ?? 'PHP';
                @endphp
                <li
                    class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900"
                    wire:key="bcr-{{ $req->id }}"
                >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ BookingChangeRequest::typeLabel($req->type) }}
                            </p>
                            <p class="text-xs text-zinc-500">
                                {{ $req->requester?->name ?? 'Member' }}
                                · {{ $req->created_at?->isoFormat('MMM D, YYYY h:mm a') }}
                            </p>
                            @if ($b)
                                <p class="mt-2 text-sm text-zinc-700 dark:text-zinc-300">
                                    <span class="font-medium">{{ $b->court?->name ?? 'Court' }}</span>
                                    <span class="text-zinc-500">·</span>
                                    {{ $b->starts_at?->isoFormat('MMM D, h:mm a') }}
                                    <span class="text-zinc-400">–</span>
                                    {{ $b->ends_at?->format('h:mm a') }}
                                </p>
                                <p class="text-xs text-zinc-500">
                                    {{ Booking::statusDisplayLabel($b->status) }}
                                    @if ($b->user)
                                        <span class="text-zinc-400">·</span>
                                        Member: {{ $b->user->email }}
                                    @endif
                                </p>
                            @endif
                            @if ($req->type === BookingChangeRequest::TYPE_REFUND_CREDIT && $req->offered_credit_cents !== null)
                                <p class="mt-2 text-sm font-medium text-emerald-800 dark:text-emerald-200">
                                    Proposed credit:
                                    {{ Money::formatMinor((int) $req->offered_credit_cents, $cur) }}
                                </p>
                            @endif
                            @if ($req->type === BookingChangeRequest::TYPE_RESCHEDULE && $req->requested_starts_at && $req->requested_ends_at)
                                <p class="mt-2 text-sm text-zinc-800 dark:text-zinc-200">
                                    Requested new time:
                                    {{ $req->requested_starts_at->isoFormat('MMM D, h:mm a') }}
                                    <span class="text-zinc-400">–</span>
                                    {{ $req->requested_ends_at->format('h:mm a') }}
                                </p>
                            @endif
                            @if ($req->member_note)
                                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                                    <span class="font-semibold text-zinc-700 dark:text-zinc-300">Member:</span>
                                    {{ $req->member_note }}
                                </p>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                wire:click="openAccept('{{ $req->id }}')"
                                class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold uppercase tracking-wide text-white hover:bg-emerald-700"
                            >
                                Accept
                            </button>
                            <button
                                type="button"
                                wire:click="openReject('{{ $req->id }}')"
                                class="rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-semibold text-zinc-700 dark:border-zinc-600 dark:text-zinc-300"
                            >
                                Decline
                            </button>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($acceptingId)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-900/60 p-4"
            wire:click="cancelAccept"
        >
            <div
                class="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-700 dark:bg-zinc-900"
                wire:click.stop
            >
                <h3 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Accept request</h3>
                @if ($acceptingType === BookingChangeRequest::TYPE_REFUND_CREDIT)
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                        Credit amount (minor units — adjust if needed before confirming).
                    </p>
                    <label class="mt-3 block text-xs font-bold uppercase tracking-wider text-zinc-500">
                        Credit amount (centavos)
                    </label>
                    <input
                        type="text"
                        inputmode="numeric"
                        wire:model="acceptRefundCredit"
                        class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                    />
                    @error('acceptRefundCredit')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                @endif
                <label class="mt-4 block text-xs font-bold uppercase tracking-wider text-zinc-500">
                    Internal note (optional)
                </label>
                <textarea
                    wire:model="acceptReviewNote"
                    rows="2"
                    class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                    placeholder="Shown only in activity log context"
                ></textarea>
                @error('acceptReviewNote')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                <div class="mt-4 flex justify-end gap-2">
                    <button
                        type="button"
                        wire:click="cancelAccept"
                        class="rounded-lg border border-zinc-200 px-3 py-2 text-sm font-semibold dark:border-zinc-600"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        wire:click="confirmAccept"
                        class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-bold text-white hover:bg-emerald-700"
                    >
                        Confirm accept
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($rejectingId)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-900/60 p-4"
            wire:click="cancelReject"
        >
            <div
                class="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-700 dark:bg-zinc-900"
                wire:click.stop
            >
                <h3 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Decline request</h3>
                <textarea
                    wire:model="rejectNote"
                    rows="3"
                    class="mt-4 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                    placeholder="Optional note (internal)"
                ></textarea>
                @error('rejectNote')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                <div class="mt-4 flex justify-end gap-2">
                    <button
                        type="button"
                        wire:click="cancelReject"
                        class="rounded-lg border border-zinc-200 px-3 py-2 text-sm font-semibold dark:border-zinc-600"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        wire:click="confirmReject"
                        class="rounded-lg bg-red-600 px-3 py-2 text-sm font-bold text-white hover:bg-red-700"
                    >
                        Decline
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
