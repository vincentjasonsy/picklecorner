@php
    use App\Support\Money;

    $st = $giftCard->adminStatusLabel();
@endphp

<div class="mx-auto max-w-4xl space-y-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a
            href="{{ $this->giftCardsIndexUrl() }}"
            wire:navigate
            class="text-sm font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
        >
            ← Back to gift cards
        </a>
        @if ($giftCard->cancelled_at === null)
            <button
                type="button"
                wire:click="cancelGiftCard"
                wire:confirm="Cancel this gift card? The code will stop working everywhere."
                class="text-sm font-semibold text-red-600 hover:text-red-700 dark:text-red-400"
            >
                Cancel card
            </button>
        @endif
    </div>

    <div>
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="font-display text-2xl font-bold text-zinc-900 dark:text-white">
                {{ $giftCard->code }}
            </h1>
            <span
                class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $this->statusBadgeClasses($st) }}"
            >
                {{ $st }}
            </span>
            @if ($giftCard->isPlatformWide())
                <span
                    class="rounded-full bg-violet-100 px-2.5 py-0.5 text-xs font-semibold text-violet-900 dark:bg-violet-950/50 dark:text-violet-200"
                >
                    All venues
                </span>
            @endif
        </div>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
            @if ($giftCard->isPlatformWide())
                Platform give-back — redeemable at any court client.
            @else
                Venue:
                <a
                    href="{{ route('admin.court-clients.edit', $giftCard->courtClient) }}"
                    wire:navigate
                    class="font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                >
                    {{ $giftCard->courtClient?->name ?? '—' }}
                </a>
            @endif
        </p>
        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                    Type
                </dt>
                <dd class="mt-0.5 text-zinc-800 dark:text-zinc-200">
                    {{ $giftCard->isPercentValue() ? 'Percentage off' : 'Fixed amount' }}
                </dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                    Discount
                </dt>
                <dd class="mt-0.5 text-zinc-800 dark:text-zinc-200">
                    @if ($giftCard->isPercentValue())
                        {{ $giftCard->percent_off }}% of each booking total (same code every time). Reference:
                        {{ Money::formatMinor($giftCard->face_value_cents, $giftCard->currency) }}
                    @else
                        Up to
                        {{ Money::formatMinor($giftCard->face_value_cents, $giftCard->currency) }}
                        off per booking (reusable; not a declining balance).
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                    Created
                </dt>
                <dd class="mt-0.5 text-zinc-800 dark:text-zinc-200">
                    {{ $giftCard->created_at?->isoFormat('MMM D, YYYY g:mma') ?? '—' }}
                </dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                    Issued by
                </dt>
                <dd class="mt-0.5 text-zinc-800 dark:text-zinc-200">
                    {{ $giftCard->creator?->name ?? '—' }}
                </dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                    Uses (bookings)
                </dt>
                <dd class="mt-0.5 text-zinc-800 dark:text-zinc-200">
                    {{ number_format($giftCard->bookingsUsingGiftCount()) }}
                    @if ($giftCard->max_redemptions_total !== null)
                        / {{ number_format($giftCard->max_redemptions_total) }} max total
                    @else
                        <span class="text-zinc-500">(no total cap)</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                    Per guest cap
                </dt>
                <dd class="mt-0.5 text-zinc-800 dark:text-zinc-200">
                    @if ($giftCard->max_redemptions_per_user !== null)
                        {{ number_format($giftCard->max_redemptions_per_user) }} booking(s) per user max
                    @else
                        <span class="text-zinc-500">No per-guest cap</span>
                    @endif
                </dd>
            </div>
        </dl>
    </div>

    <div
        class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900"
    >
        <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Edit details</h2>
        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
            Code, amounts, and venue scope cannot be changed here. Update labels, notes, redeem window, and use limits
            (cannot set caps below existing usage).
        </p>

        <form wire:submit="saveMetadata" class="mt-4 space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Internal title
                    </label>
                    <input
                        type="text"
                        wire:model="editTitle"
                        class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                    />
                    @error('editTitle')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Event / campaign label
                    </label>
                    <input
                        type="text"
                        wire:model="editEventLabel"
                        class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                    />
                    @error('editEventLabel')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Redeemable from
                    </label>
                    <input
                        type="datetime-local"
                        wire:model="editValidFrom"
                        class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                    />
                    @error('editValidFrom')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Redeemable until
                    </label>
                    <input
                        type="datetime-local"
                        wire:model="editValidUntil"
                        class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                    />
                    @error('editValidUntil')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Max total uses
                    </label>
                    <input
                        type="number"
                        min="1"
                        wire:model="editMaxRedemptionsTotal"
                        class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                        placeholder="Leave empty for unlimited"
                    />
                    @error('editMaxRedemptionsTotal')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Max uses per guest
                    </label>
                    <input
                        type="number"
                        min="1"
                        wire:model="editMaxRedemptionsPerUser"
                        class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                        placeholder="Leave empty for unlimited"
                    />
                    @error('editMaxRedemptionsPerUser')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Notes
                    </label>
                    <textarea
                        wire:model="editNotes"
                        rows="3"
                        class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                    ></textarea>
                    @error('editNotes')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="flex justify-end">
                <button
                    type="submit"
                    class="rounded-lg bg-zinc-800 px-4 py-2 text-sm font-bold uppercase tracking-wide text-white hover:bg-zinc-900 dark:bg-zinc-200 dark:text-zinc-900 dark:hover:bg-white"
                >
                    Save changes
                </button>
            </div>
        </form>
    </div>

    <div
        class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900"
    >
        <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Bookings using this card</h2>
        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
            Bookings where this code was applied (gift card discount recorded on the booking).
        </p>

        @if ($bookings->isEmpty())
            <p class="mt-6 text-center text-sm text-zinc-500 dark:text-zinc-400">No bookings yet.</p>
        @else
            <div class="mt-4 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-left text-sm dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/80">
                        <tr>
                            <th class="px-3 py-2 text-xs font-semibold uppercase tracking-wider text-zinc-500">
                                When
                            </th>
                            <th class="px-3 py-2 text-xs font-semibold uppercase tracking-wider text-zinc-500">
                                Venue
                            </th>
                            <th class="px-3 py-2 text-xs font-semibold uppercase tracking-wider text-zinc-500">
                                Court
                            </th>
                            <th class="px-3 py-2 text-xs font-semibold uppercase tracking-wider text-zinc-500">
                                Guest
                            </th>
                            <th class="px-3 py-2 text-xs font-semibold uppercase tracking-wider text-zinc-500">
                                Applied
                            </th>
                            <th class="px-3 py-2 text-xs font-semibold uppercase tracking-wider text-zinc-500">
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($bookings as $b)
                            <tr wire:key="gcb-{{ $b->id }}">
                                <td class="whitespace-nowrap px-3 py-2 text-zinc-700 dark:text-zinc-300">
                                    {{ $b->starts_at?->isoFormat('MMM D, YYYY g:mma') ?? '—' }}
                                </td>
                                <td class="px-3 py-2 text-zinc-600 dark:text-zinc-400">
                                    {{ $b->courtClient?->name ?? '—' }}
                                </td>
                                <td class="px-3 py-2 text-zinc-600 dark:text-zinc-400">
                                    {{ $b->court?->name ?? '—' }}
                                </td>
                                <td class="px-3 py-2 text-zinc-600 dark:text-zinc-400">
                                    {{ $b->user?->name ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 font-medium text-zinc-800 dark:text-zinc-200">
                                    @if (($b->gift_card_redeemed_cents ?? 0) > 0)
                                        {{ Money::formatMinor((int) $b->gift_card_redeemed_cents, $b->currency ?? 'PHP') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-xs font-medium text-zinc-600 dark:text-zinc-400">
                                    {{ $b->status }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>
        @endif
    </div>
</div>
