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

    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80">
        <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-800">
            <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Players</h2>
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
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ ucfirst($p->status) }}</td>
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
                                    @if ($p->status === OpenPlayParticipant::STATUS_PENDING)
                                        <button
                                            type="button"
                                            wire:click="acceptParticipant('{{ $p->id }}')"
                                            class="text-xs font-bold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                                        >
                                            Accept
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="rejectParticipant('{{ $p->id }}')"
                                            wire:confirm="Decline this request?"
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
                                            wire:click="removeParticipant('{{ $p->id }}')"
                                            wire:confirm="Remove this player from the list?"
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
</div>
