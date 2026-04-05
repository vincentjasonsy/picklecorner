@php
    $cc = $this->courtClient;
    $rows = $this->bookingsPaginator;
@endphp

<div class="space-y-6">
    @if (! $cc)
        <p class="text-sm text-red-600 dark:text-red-400">No venue is assigned to your desk account.</p>
    @else
        <p class="text-sm leading-relaxed text-stone-600 dark:text-stone-400">
            Requests you logged at the counter for
            <strong class="text-stone-900 dark:text-stone-100">{{ $cc->name }}</strong>. Only your submissions appear
            here.
        </p>

        <div
            class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-900"
        >
            <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-stone-200 bg-stone-50/80 dark:border-stone-700 dark:bg-stone-800/50">
                        <th class="px-4 py-3.5 font-semibold text-stone-700 dark:text-stone-300">Submitted</th>
                        <th class="px-4 py-3.5 font-semibold text-stone-700 dark:text-stone-300">Guest</th>
                        <th class="px-4 py-3.5 font-semibold text-stone-700 dark:text-stone-300">Court</th>
                        <th class="px-4 py-3.5 font-semibold text-stone-700 dark:text-stone-300">Reservation</th>
                        <th class="px-4 py-3.5 font-semibold text-stone-700 dark:text-stone-300">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $b)
                        <tr
                            class="border-b border-stone-100 transition-colors hover:bg-stone-50/80 dark:border-stone-800 dark:hover:bg-stone-800/40"
                            wire:key="desk-req-{{ $b->id }}"
                        >
                            <td class="px-4 py-3.5 text-stone-600 dark:text-stone-400">
                                {{ $b->created_at?->timezone(config('app.timezone'))->isoFormat('MMM D, h:mm a') }}
                            </td>
                            <td class="px-4 py-3.5">
                                <p class="font-medium text-stone-900 dark:text-stone-100">
                                    {{ $b->user?->name ?? '—' }}
                                </p>
                                <p class="text-xs text-stone-500">{{ $b->user?->email }}</p>
                            </td>
                            <td class="px-4 py-3.5 text-stone-700 dark:text-stone-300">
                                {{ $b->court?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3.5 text-xs text-stone-600 dark:text-stone-400">
                                {{ $this->slotSummary($b) }}
                            </td>
                            <td class="px-4 py-3.5">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $this->statusBadgeClasses($b->status) }}"
                                >
                                    {{ $this->statusLabel($b->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-stone-500">
                                Nothing in the log yet. Start with
                                <a
                                    href="{{ route('desk.booking-request') }}"
                                    wire:navigate
                                    class="font-semibold text-teal-700 underline decoration-teal-600/40 dark:text-teal-400"
                                >
                                    New booking request
                                </a>
                                .
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        @if ($rows->hasPages())
            <div class="text-sm text-stone-600 dark:text-stone-400">
                {{ $rows->links() }}
            </div>
        @endif
    @endif
</div>
