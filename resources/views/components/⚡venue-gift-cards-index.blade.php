<?php

use App\Livewire\Concerns\WithDashboardTable;
use App\Models\GiftCard;
use App\Services\ActivityLogger;
use App\Services\GiftCardService;
use App\Support\Money;
use App\Support\PesosMoneyForm;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::venue-portal'), Title('Gift cards')] class extends Component
{
    use WithDashboardTable;

    #[Url]
    public string $q = '';

    public bool $showCreateForm = false;

    public string $createValueType = 'fixed';

    public string $createFaceValuePesos = '';

    public string $createPercentOff = '';

    public string $createTitle = '';

    public string $createEventLabel = '';

    public string $createValidFrom = '';

    public string $createValidUntil = '';

    public string $createCustomCode = '';

    public string $createNotes = '';

    public string $createMaxRedemptionsTotal = '';

    public string $createMaxRedemptionsPerUser = '';

    /** @return list<string> */
    protected function sortableColumns(): array
    {
        return ['created_at', 'balance_cents', 'face_value_cents', 'code'];
    }

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function courtClient()
    {
        return auth()->user()->administeredCourtClient;
    }

    #[Computed]
    public function giftCardsPaginator()
    {
        $c = $this->courtClient;
        if (! $c) {
            return GiftCard::query()->whereRaw('1 = 0')->paginate($this->perPage);
        }

        $query = GiftCard::query()
            ->with(['courtClient', 'creator'])
            ->where('court_client_id', $c->id);

        if ($this->q !== '') {
            $s = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $this->q).'%';
            $query->where(function ($q) use ($s) {
                $q->where('code', 'like', $s)
                    ->orWhere('title', 'like', $s)
                    ->orWhere('event_label', 'like', $s);
            });
        }

        if ($this->sortField !== '' && in_array($this->sortField, $this->sortableColumns(), true)) {
            $query->orderBy($this->sortField, $this->sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderByDesc('created_at');
        }

        return $query->paginate($this->perPage);
    }

    public function createGiftCard(): void
    {
        $c = $this->courtClient;
        abort_unless($c, 403);

        $validated = $this->validate([
            'createValueType' => ['required', 'in:fixed,percent'],
            'createFaceValuePesos' => ['required', 'string', 'regex:/'.PesosMoneyForm::pesoFieldRegex().'/'],
            'createPercentOff' => [
                \Illuminate\Validation\Rule::excludeUnless(fn () => $this->createValueType === 'percent'),
                'required',
                'integer',
                'min:1',
                'max:100',
            ],
            'createTitle' => ['nullable', 'string', 'max:120'],
            'createEventLabel' => ['nullable', 'string', 'max:160'],
            'createValidFrom' => ['nullable', 'date'],
            'createValidUntil' => ['nullable', 'date'],
            'createCustomCode' => ['nullable', 'string', 'max:48'],
            'createNotes' => ['nullable', 'string', 'max:2000'],
            'createMaxRedemptionsTotal' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'createMaxRedemptionsPerUser' => ['nullable', 'integer', 'min:1', 'max:999999'],
        ], [], [
            'createFaceValuePesos' => $this->createValueType === 'percent' ? 'max discount budget' : 'face value',
        ]);

        $cents = PesosMoneyForm::pesoFieldToCents($validated['createFaceValuePesos']);
        if ($cents === null || $cents < 1) {
            $this->addError('createFaceValuePesos', 'Enter a positive amount.');

            return;
        }

        $validFrom = $this->createValidFrom !== '' ? Carbon::parse($this->createValidFrom) : null;
        $validUntil = $this->createValidUntil !== '' ? Carbon::parse($this->createValidUntil) : null;

        if ($validFrom !== null && $validUntil !== null && $validUntil->lt($validFrom)) {
            $this->addError('createValidUntil', 'End must be on or after the start.');

            return;
        }

        $maxTotal = $this->createMaxRedemptionsTotal !== '' ? (int) $this->createMaxRedemptionsTotal : null;
        $maxPerUser = $this->createMaxRedemptionsPerUser !== '' ? (int) $this->createMaxRedemptionsPerUser : null;

        if ($maxTotal !== null && $maxPerUser !== null && $maxPerUser > $maxTotal) {
            $this->addError('createMaxRedemptionsPerUser', 'Max uses per user cannot exceed max total uses.');

            return;
        }

        $valueType = $validated['createValueType'] === 'percent'
            ? GiftCard::VALUE_PERCENT
            : GiftCard::VALUE_FIXED;
        $percentOff = isset($validated['createPercentOff'])
            ? (int) $validated['createPercentOff']
            : null;

        try {
            $card = GiftCardService::issue(
                $c,
                $valueType,
                $cents,
                $percentOff,
                $this->createTitle,
                $this->createEventLabel,
                $validFrom,
                $validUntil,
                $this->createCustomCode !== '' ? $this->createCustomCode : null,
                $this->createNotes,
                auth()->id(),
                $maxTotal,
                $maxPerUser,
            );
        } catch (\InvalidArgumentException $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'already in use')) {
                $this->addError('createCustomCode', $msg);
            } elseif (str_contains($msg, 'per user')) {
                $this->addError('createMaxRedemptionsPerUser', $msg);
            } elseif (str_contains($msg, 'total uses')) {
                $this->addError('createMaxRedemptionsTotal', $msg);
            } else {
                $this->addError('createCustomCode', $msg);
            }

            return;
        }

        ActivityLogger::log(
            'gift_card.created',
            [
                'code' => $card->code,
                'value_type' => $card->value_type,
                'percent_off' => $card->percent_off,
                'face_value_cents' => $card->face_value_cents,
                'court_client_id' => $card->court_client_id,
            ],
            $card,
            "Gift card {$card->code} created for “{$c->name}”",
            null,
            $c->id,
        );

        $this->reset([
            'createValueType',
            'createFaceValuePesos',
            'createPercentOff',
            'createTitle',
            'createEventLabel',
            'createValidFrom',
            'createValidUntil',
            'createCustomCode',
            'createNotes',
            'createMaxRedemptionsTotal',
            'createMaxRedemptionsPerUser',
        ]);
        $this->createValueType = 'fixed';
        $this->showCreateForm = false;
        unset($this->giftCardsPaginator);

        session()->flash('status', "Gift card {$card->code} created.");
    }

    public function cancelGiftCard(string $id): void
    {
        $c = $this->courtClient;
        abort_unless($c, 403);

        $card = GiftCard::query()
            ->where('id', $id)
            ->where('court_client_id', $c->id)
            ->firstOrFail();

        if ($card->cancelled_at !== null) {
            return;
        }

        $card->cancelled_at = now();
        $card->save();

        ActivityLogger::log(
            'gift_card.cancelled',
            ['code' => $card->code],
            $card,
            "Gift card {$card->code} cancelled",
            null,
            $c->id,
        );

        unset($this->giftCardsPaginator);

        session()->flash('status', "Gift card {$card->code} cancelled.");
    }

    public function statusBadgeClasses(string $status): string
    {
        return match ($status) {
            GiftCard::STATUS_ACTIVE => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200',
            GiftCard::STATUS_SCHEDULED => 'bg-sky-100 text-sky-800 dark:bg-sky-950/50 dark:text-sky-200',
            GiftCard::STATUS_EXHAUSTED, GiftCard::STATUS_EXPIRED => 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200',
            GiftCard::STATUS_CANCELLED => 'bg-red-100 text-red-800 dark:bg-red-950/50 dark:text-red-200',
            default => 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200',
        };
    }
};
?>

@php
    $cards = $this->giftCardsPaginator;
    $headerSortField = $sortField !== '' ? $sortField : 'created_at';
    $headerSortDir = $sortField !== '' ? $sortDirection : 'desc';
    $vc = $this->courtClient;
@endphp

<div class="space-y-8">
    @if (! $vc)
        <p class="text-sm text-red-600">No venue is assigned to your account.</p>
    @else
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                Gift cards for <strong>{{ $vc->name }}</strong> only. Codes redeem at your venue.
            </p>
            <button
                type="button"
                wire:click="$toggle('showCreateForm')"
                class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-bold uppercase tracking-wide text-white hover:bg-emerald-700 dark:bg-emerald-500"
            >
                {{ $showCreateForm ? 'Close form' : 'New gift card' }}
            </button>
        </div>

        @if ($showCreateForm)
            <div
                class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900"
                wire:key="venue-gift-card-create"
            >
                <h2 class="font-display text-base font-bold text-zinc-900 dark:text-white">Create gift card</h2>
                <form wire:submit="createGiftCard" class="mt-4 grid gap-4 sm:grid-cols-2">
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
                    <th class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-300">Created</th>
                    <th class="px-4 py-3 text-right font-semibold text-zinc-700 dark:text-zinc-300"></th>
                </tr>
            </x-slot:head>

            @forelse ($cards as $card)
                @php
                    $st = $card->adminStatusLabel();
                @endphp
                <tr wire:key="vgc-{{ $card->id }}">
                    <td class="px-4 py-3 font-mono text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ $card->code }}
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
                        @if ($card->max_redemptions_per_user !== null)
                            <span class="mt-0.5 block text-[10px] text-zinc-500">
                                ≤ {{ number_format($card->max_redemptions_per_user) }}/guest
                            </span>
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
                                href="{{ route('venue.gift-cards.show', $card) }}"
                                wire:navigate
                                class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
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
                    <td colspan="8" class="px-4 py-8 text-center text-zinc-500">No gift cards yet.</td>
                </tr>
            @endforelse
        </x-dashboard.data-table>
    @endif
</div>
