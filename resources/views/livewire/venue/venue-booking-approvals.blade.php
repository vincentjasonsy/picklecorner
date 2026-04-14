@php
    use App\Models\CourtClient;

    $cc = $this->courtClient;
    $policy = $cc?->deskBookingPolicyNormalized() ?? CourtClient::DESK_BOOKING_POLICY_MANUAL;
    $isManualPolicy = $policy === CourtClient::DESK_BOOKING_POLICY_MANUAL;
@endphp

<div class="space-y-6">
    @if ($cc)
        <div
            @class([
                'rounded-xl border p-4 text-sm',
                $isManualPolicy
                    ? 'border-emerald-200 bg-emerald-50/90 text-emerald-950 dark:border-emerald-900/40 dark:bg-emerald-950/35 dark:text-emerald-100'
                    : 'border-amber-200 bg-amber-50/90 text-amber-950 dark:border-amber-900/40 dark:bg-amber-950/35 dark:text-amber-100',
            ])
        >
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-zinc-900 dark:text-zinc-100">
                        Desk handling:
                        <span class="font-bold">{{ $cc->deskBookingPolicyShortLabel() }}</span>
                    </p>
                    <p class="mt-2 text-zinc-800 dark:text-zinc-200">
                        {{ $cc->deskBookingPolicyAdminBannerText() }}
                    </p>
                </div>
                <a
                    href="{{ route('venue.settings') }}"
                    wire:navigate
                    class="shrink-0 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-xs font-bold uppercase tracking-wide text-zinc-800 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
                >
                    Auto approve / deny →
                </a>
            </div>
        </div>
    @endif

    @if ($isManualPolicy)
        <p class="text-sm text-zinc-600 dark:text-zinc-400">
            When handling is <strong>manual review</strong>, each submission from the desk portal appears below until you
            approve or deny it.
        </p>
    @endif

    @if ($this->pendingBookingGroups->isEmpty())
        <p class="rounded-xl border border-dashed border-zinc-300 px-6 py-10 text-center text-sm text-zinc-500 dark:border-zinc-600">
            @if ($isManualPolicy)
                No pending manual booking requests from desk staff.
            @else
                No pending queue — new desk submissions are handled automatically with your current setting.
            @endif
        </p>
    @else
        <ul class="space-y-4">
            @foreach ($this->pendingBookingGroups as $group)
                @php($head = $group->first())
                <li
                    wire:key="pend-req-{{ $head->booking_request_id ?? $head->id }}"
                    class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $head->user?->name ?? 'Guest' }}</p>
                            <p class="text-xs text-zinc-500">{{ $head->user?->email }}</p>
                            @if ($group->count() > 1)
                                <p class="mt-1 text-xs font-semibold text-emerald-800 dark:text-emerald-300">
                                    One request · {{ $group->count() }} courts
                                </p>
                            @endif
                            <p
                                class="mt-1 break-all font-mono text-[11px] leading-snug text-zinc-500 dark:text-zinc-400"
                                title="Use this reference when contacting support"
                            >
                                Reference: {{ $head->transactionReference() }}
                            </p>
                            <ul class="mt-3 space-y-2 text-sm text-zinc-700 dark:text-zinc-300">
                                @foreach ($group as $b)
                                    <li wire:key="pend-line-{{ $b->id }}" class="border-l-2 border-zinc-200 pl-3 dark:border-zinc-600">
                                        <span class="font-medium">{{ $b->court?->name ?? 'Court' }}</span>
                                        <span class="text-zinc-500"> · </span>
                                        {{ $this->slotLabel($b) }}
                                    </li>
                                @endforeach
                            </ul>
                            @if ($head->deskSubmitter)
                                <p class="mt-2 text-xs text-zinc-500">
                                    Submitted by desk: {{ $head->deskSubmitter->name }}
                                </p>
                            @endif
                            @if ($head->notes)
                                <p class="mt-2 text-xs text-zinc-600 dark:text-zinc-400">{{ $head->notes }}</p>
                            @endif
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-2">
                            <button
                                type="button"
                                wire:click="approve('{{ $head->id }}')"
                                class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold uppercase tracking-wide text-white hover:bg-emerald-700"
                            >
                                Approve
                            </button>
                            <button
                                type="button"
                                wire:click="openDeny('{{ $head->id }}')"
                                class="rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-semibold text-zinc-700 dark:border-zinc-600 dark:text-zinc-300"
                            >
                                Deny
                            </button>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($denyBookingId)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-900/60 p-4"
            wire:click="cancelDeny"
        >
            <div
                class="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-700 dark:bg-zinc-900"
                wire:click.stop
            >
                <h3 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Deny request</h3>
                <p class="mt-1 text-xs text-zinc-500">Reason is stored on the booking notes.</p>
                <textarea
                    wire:model="denyReason"
                    rows="3"
                    class="mt-4 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                    placeholder="Reason for denial"
                ></textarea>
                @error('denyReason')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                <div class="mt-4 flex justify-end gap-2">
                    <button
                        type="button"
                        wire:click="cancelDeny"
                        class="rounded-lg border border-zinc-200 px-3 py-2 text-sm font-semibold dark:border-zinc-600"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        wire:click="confirmDeny"
                        class="rounded-lg bg-red-600 px-3 py-2 text-sm font-bold text-white hover:bg-red-700"
                    >
                        Deny request
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
