@php
    use App\Models\Booking;
    use App\Models\OpenPlayParticipant;
    use App\Support\Money;

    $tz = config('app.timezone', 'UTC');
@endphp

<div class="space-y-6">
    <div>
        <h1 class="font-display text-2xl font-extrabold tracking-tight text-zinc-900 dark:text-white">My games</h1>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
            Every court you’ve booked — newest first. Nice work keeping the rallies going.
        </p>
    </div>

    @if ($openPlayJoins->isNotEmpty())
        <div
            class="rounded-2xl border border-violet-200 bg-violet-50/40 p-5 dark:border-violet-900/40 dark:bg-violet-950/20"
        >
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-display text-base font-bold text-zinc-900 dark:text-white">Upcoming open plays (joined)</h2>
                <a
                    href="{{ route('account.court-open-plays.index') }}"
                    wire:navigate
                    class="text-xs font-bold text-violet-600 dark:text-violet-400"
                >
                    Host hub
                </a>
            </div>
            <ul class="mt-3 space-y-2">
                @foreach ($openPlayJoins as $row)
                    @php($b = $row->booking)
                    @if ($b)
                        <li class="flex flex-wrap items-center justify-between gap-2 text-sm">
                            <span class="text-zinc-800 dark:text-zinc-200">
                                {{ $b->courtClient?->name }} · {{ $b->starts_at?->timezone($tz)->format('M j, g:i A') }}
                            </span>
                            <span class="text-xs font-semibold text-violet-800 dark:text-violet-200">
                                {{ $row->status === OpenPlayParticipant::STATUS_ACCEPTED ? 'Confirmed' : 'Pending' }}
                            </span>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    @endif

    <div
        class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80"
    >
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50/90 dark:border-zinc-800 dark:bg-zinc-950/80">
                    <tr class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        <th class="px-4 py-3">When</th>
                        <th class="px-4 py-3">Venue</th>
                        <th class="px-4 py-3">Court</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($bookings as $b)
                        <tr wire:key="mb-{{ $b->id }}" class="text-zinc-800 dark:text-zinc-200">
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="font-medium">{{ $b->starts_at?->timezone($tz)->format('M j, Y') }}</span>
                                <span class="block text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $b->starts_at?->timezone($tz)->format('g:i A') }}
                                    –
                                    {{ $b->ends_at?->timezone($tz)->format('g:i A') }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-medium">{{ $b->courtClient?->name ?? '—' }}</span>
                                @if ($b->courtClient?->city)
                                    <span class="block text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $b->courtClient->city }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                {{ $b->court?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-200"
                                >
                                    {{ Booking::statusDisplayLabel($b->status) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold">
                                @if ($b->amount_cents !== null)
                                    {{ Money::formatMinor($b->amount_cents, $b->currency) }}
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-16 text-center">
                                <x-icon name="document-text" class="mx-auto size-12 text-zinc-400 dark:text-zinc-500" />
                                <p class="mt-4 font-medium text-zinc-700 dark:text-zinc-300">No bookings yet</p>
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                    When you reserve court time, your matches will appear in this log.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($bookings->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-800">
                {{ $bookings->links() }}
            </div>
        @endif
    </div>
</div>
