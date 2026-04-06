@php
    use App\Support\Money;

    $cards = $this->giftCardsPaginator;
    $headerSortField = $sortField !== '' ? $sortField : 'created_at';
    $headerSortDir = $sortField !== '' ? $sortDirection : 'desc';
    $venues = $this->venuesForCoach;
@endphp

<div class="space-y-8">
    @if ($venues->isEmpty())
        <div
            class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100"
            role="status"
        >
            Turn on at least one venue under
            <a href="{{ route('account.coach.courts') }}" wire:navigate class="font-bold underline">Venues you coach</a>
            before you can issue gift cards for that venue.
        </div>
    @else
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
                Issue <strong>fixed peso</strong> or <strong>percentage-off</strong> cards for a venue where you coach.
                Codes redeem when members book that venue — same as venue-issued cards. You’ll only see cards you
                created here.
            </p>
            <button
                type="button"
                wire:click="$toggle('showCreateForm')"
                class="rounded-lg bg-violet-600 px-4 py-2 text-xs font-bold uppercase tracking-wide text-white hover:bg-violet-500 dark:bg-violet-500"
            >
                {{ $showCreateForm ? 'Close form' : 'New gift card' }}
            </button>
        </div>

        @if ($showCreateForm)
            <div
                class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900"
                wire:key="coach-gift-card-create"
            >
                <h2 class="font-display text-base font-bold text-zinc-900 dark:text-white">Create gift card</h2>
                <form wire:submit="createGiftCard" class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Venue
                        </label>
                        <select
                            wire:model="createCourtClientId"
                            class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                        >
                            <option value="">Select venue</option>
                            @foreach ($venues as $v)
                                <option value="{{ $v->id }}">{{ $v->name }}@if ($v->city) — {{ $v->city }} @endif</option>
                            @endforeach
                        </select>
                        @error('createCourtClientId')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Value type
                        </label>
                        <select
                            wire:model.live="createValueType"
                            class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                        >
                            <option value="fixed">Fixed peso amount</option>
                            <option value="percent">Percentage off</option>
                        </select>
                    </div>
                    @if ($createValueType === 'percent')
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Percent off (%)
                            </label>
                            <input
                                type="number"
                                min="1"
                                max="100"
                                wire:model="createPercentOff"
                                class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                placeholder="e.g. 25"
                            />
                            @error('createPercentOff')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            @if ($createValueType === 'percent')
                                Max total discount (₱)
                            @else
                                Face value (₱)
                            @endif
                        </label>
                        <input
                            type="text"
                            inputmode="decimal"
                            wire:model="createFaceValuePesos"
                            class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                            placeholder="{{ $createValueType === 'percent' ? 'e.g. 5000 cap' : 'e.g. 2000' }}"
                        />
                        @error('createFaceValuePesos')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Custom code (optional)
                        </label>
                        <input
                            type="text"
                            wire:model="createCustomCode"
                            class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm uppercase dark:border-zinc-700 dark:bg-zinc-950"
                        />
                        @error('createCustomCode')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Internal title
                        </label>
                        <input type="text" wire:model="createTitle" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Event / campaign label
                        </label>
                        <input type="text" wire:model="createEventLabel" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Redeemable from (optional)
                        </label>
                        <input type="datetime-local" wire:model="createValidFrom" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Redeemable until (optional)
                        </label>
                        <input type="datetime-local" wire:model="createValidUntil" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Max total uses (optional)
                        </label>
                        <input
                            type="number"
                            min="1"
                            wire:model="createMaxRedemptionsTotal"
                            class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                            placeholder="Unlimited if empty"
                        />
                        @error('createMaxRedemptionsTotal')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Max uses per guest (optional)
                        </label>
                        <input
                            type="number"
                            min="1"
                            wire:model="createMaxRedemptionsPerUser"
                            class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                            placeholder="Unlimited if empty"
                        />
                        @error('createMaxRedemptionsPerUser')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Notes (optional)
                        </label>
                        <textarea wire:model="createNotes" rows="2" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"></textarea>
                    </div>
                    <div class="sm:col-span-2 flex justify-end">
                        <button
                            type="submit"
                            class="rounded-lg bg-zinc-800 px-4 py-2 text-sm font-bold uppercase tracking-wide text-white hover:bg-zinc-900 dark:bg-zinc-200 dark:text-zinc-900 dark:hover:bg-white"
                        >
                            Issue card
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <x-dashboard.data-table :paginator="$cards">
            <x-slot:toolbar>
                <x-dashboard.table-search wire:model.live.debounce.300ms="q" placeholder="Code, title, or event" />
                <x-dashboard.table-filter wire:model.live="courtFilter" label="Venue">
                    <option value="">All venues</option>
                    @foreach ($venues as $v)
                        <option value="{{ $v->id }}">{{ $v->name }}</option>
                    @endforeach
                </x-dashboard.table-filter>
            </x-slot:toolbar>

            <x-slot:toolbarEnd>
                <x-dashboard.table-per-page />
            </x-slot:toolbarEnd>

            <x-slot:head>
                <tr>
                    <x-dashboard.sortable-th
                        column="code"
                        label="Code"
                        :active="$headerSortField"
                        :direction="$headerSortDir"
                    />
                    <th class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-300">Venue</th>
                    <th class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-300">Type</th>
                    <x-dashboard.sortable-th
                        column="face_value_cents"
                        label="Value"
                        :active="$headerSortField"
                        :direction="$headerSortDir"
                    />
                    <x-dashboard.sortable-th
                        column="balance_cents"
                        label="Balance"
                        :active="$headerSortField"
                        :direction="$headerSortDir"
                    />
                    <th class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-300">Uses</th>
                    <th class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-300">Status</th>
                    <x-dashboard.sortable-th
                        column="created_at"
                        label="Created"
                        :active="$headerSortField"
                        :direction="$headerSortDir"
                    />
                    <th class="px-4 py-3 text-right font-semibold text-zinc-700 dark:text-zinc-300"></th>
                </tr>
            </x-slot:head>

            @forelse ($cards as $card)
                @php
                    $st = $card->adminStatusLabel();
                @endphp
                <tr wire:key="cgc-{{ $card->id }}">
                    <td class="px-4 py-3 font-mono text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ $card->code }}
                    </td>
                    <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ $card->courtClient?->name ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ $card->isPercentValue() ? 'Percent' : 'Fixed' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                        @if ($card->isPercentValue())
                            {{ $card->percent_off }}% · max total
                            {{ Money::formatMinor($card->face_value_cents, $card->currency) }}
                        @else
                            {{ Money::formatMinor($card->face_value_cents, $card->currency) }}
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ Money::formatMinor($card->balance_cents, $card->currency) }}
                    </td>
                    <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-400">
                        @php
                            $used = $card->bookingsUsingGiftCount();
                        @endphp
                        {{ number_format($used) }}
                        @if ($card->max_redemptions_total !== null)
                            / {{ number_format($card->max_redemptions_total) }}
                        @else
                            <span class="text-zinc-400">/ ∞</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $this->statusBadgeClasses($st) }}">
                            {{ $st }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ $card->created_at?->isoFormat('MMM D, YYYY') }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex flex-wrap items-center justify-end gap-2">
                            <a
                                href="{{ route('account.coach.gift-cards.show', $card) }}"
                                wire:navigate
                                class="text-xs font-semibold text-violet-600 hover:text-violet-700 dark:text-violet-400"
                            >
                                View
                            </a>
                            @if ($card->cancelled_at === null)
                                <button
                                    type="button"
                                    wire:click="cancelGiftCard('{{ $card->id }}')"
                                    wire:confirm="Cancel this gift card? The code will stop working everywhere."
                                    class="text-xs font-semibold text-red-600 hover:text-red-700 dark:text-red-400"
                                >
                                    Cancel
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-zinc-500">No gift cards you’ve issued yet.</td>
                </tr>
            @endforelse
        </x-dashboard.data-table>
    @endif
</div>
