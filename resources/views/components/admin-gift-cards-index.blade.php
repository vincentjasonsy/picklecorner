<?php

use App\Livewire\Concerns\WithDashboardTable;
use App\Models\CourtClient;
use App\Models\GiftCard;
use App\Models\UserType;
use App\Services\ActivityLogger;
use App\Services\GiftCardService;
use App\Support\Money;
use App\Support\PesosMoneyForm;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::admin'), Title('Gift cards')] class extends Component
{
    use WithDashboardTable;

    #[Url]
    public string $q = '';

    #[Url]
    public string $courtFilter = '';

    /** all | coaches | staff */
    #[Url]
    public string $issuedByFilter = '';

    public bool $showCreateForm = false;

    public string $createCourtClientId = '';

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

    public bool $createPlatformWide = false;

    /** @return list<string> */
    protected function sortableColumns(): array
    {
        return ['created_at', 'balance_cents', 'face_value_cents', 'code'];
    }

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function updatedCourtFilter(): void
    {
        $this->resetPage();
    }

    public function updatedIssuedByFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function clientsForSelect()
    {
        return CourtClient::query()->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function giftCardsPaginator()
    {
        $query = GiftCard::query()->with(['courtClient', 'creator.userType']);

        if ($this->issuedByFilter === 'coaches') {
            $query->whereHas('creator.userType', fn ($q) => $q->where('slug', UserType::SLUG_COACH));
        } elseif ($this->issuedByFilter === 'staff') {
            $query->where(function ($q) {
                $q->whereNull('created_by')
                    ->orWhereHas('creator.userType', fn ($q2) => $q2->where('slug', '!=', UserType::SLUG_COACH));
            });
        }

        if ($this->courtFilter !== '') {
            if ($this->courtFilter === '__platform__') {
                $query->whereNull('court_client_id');
            } else {
                $query->where('court_client_id', $this->courtFilter);
            }
        }

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
        $validated = $this->validate([
            'createCourtClientId' => [
                Rule::requiredIf(fn () => ! $this->createPlatformWide),
                'nullable',
                'uuid',
                Rule::exists('court_clients', 'id'),
            ],
            'createValueType' => ['required', 'in:fixed,percent'],
            'createFaceValuePesos' => ['required', 'string', 'regex:/'.PesosMoneyForm::pesoFieldRegex().'/'],
            'createPercentOff' => [
                Rule::excludeUnless(fn () => $this->createValueType === 'percent'),
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
            'createCourtClientId' => 'venue',
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

        $client = $this->createPlatformWide
            ? null
            : CourtClient::query()->findOrFail($validated['createCourtClientId']);

        $valueType = $validated['createValueType'] === 'percent'
            ? GiftCard::VALUE_PERCENT
            : GiftCard::VALUE_FIXED;
        $percentOff = isset($validated['createPercentOff'])
            ? (int) $validated['createPercentOff']
            : null;

        try {
            $card = GiftCardService::issue(
                $client,
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

        $description = $client !== null
            ? "Gift card {$card->code} created for “{$client->name}”"
            : "Gift card {$card->code} created (platform — all venues)";

        ActivityLogger::log(
            'gift_card.created',
            [
                'code' => $card->code,
                'value_type' => $card->value_type,
                'percent_off' => $card->percent_off,
                'face_value_cents' => $card->face_value_cents,
                'court_client_id' => $card->court_client_id,
                'platform_wide' => $client === null,
            ],
            $card,
            $description,
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
        $this->createPlatformWide = false;
        $this->showCreateForm = false;
        unset($this->giftCardsPaginator);

        session()->flash('status', "Gift card {$card->code} created.");
    }

    public function cancelGiftCard(string $id): void
    {
        $card = GiftCard::query()->findOrFail($id);

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
    $clients = $this->clientsForSelect;
@endphp

<div class="space-y-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="max-w-3xl text-sm text-zinc-600 dark:text-zinc-400">
            Issue <strong>fixed peso</strong> cards or <strong>percentage-off</strong> cards (each redemption applies the
            percent of the booking total, capped by a max total discount budget). Use
            <strong>All venues</strong> for platform-wide give-back codes redeemable at any court client. Optional redeem
            windows and <strong>event labels</strong> for campaigns.
            <span class="mt-2 block text-zinc-500 dark:text-zinc-500">
                Coaches issue venue-scoped cards from <strong>Account → Coaching → Gift cards</strong>; those appear here
                with an <strong>Issued by</strong> name and you can filter by coach-issued rows.
            </span>
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
            wire:key="gift-card-create"
        >
            <h2 class="font-display text-base font-bold text-zinc-900 dark:text-white">Create gift card</h2>
            <form wire:submit="createGiftCard" class="mt-4 grid gap-4 sm:grid-cols-2">
                <label
                    class="flex cursor-pointer items-start gap-3 rounded-lg border border-zinc-200 px-3 py-3 sm:col-span-2 dark:border-zinc-600"
                >
                    <input
                        type="checkbox"
                        wire:model.live="createPlatformWide"
                        class="mt-1 size-4 rounded border-zinc-300 dark:border-zinc-600"
                    />
                    <span class="text-sm text-zinc-800 dark:text-zinc-200">
                        <span class="font-semibold">All venues (platform give-back)</span>
                        — redeemable at any court client; use for goodwill or promotions from us.
                    </span>
                </label>
                @if (! $createPlatformWide)
                    <div class="sm:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Venue
                        </label>
                        <select
                            wire:model="createCourtClientId"
                            class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                        >
                            <option value="">Select venue</option>
                            @foreach ($clients as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                        @error('createCourtClientId')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
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
                    @error('createValueType')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
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
                    @if ($createValueType === 'percent')
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            Total peso value of discounts this code can fund across all bookings.
                        </p>
                    @endif
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Custom code (optional)
                    </label>
                    <input
                        type="text"
                        wire:model="createCustomCode"
                        class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm uppercase dark:border-zinc-700 dark:bg-zinc-950"
                        placeholder="Auto-generated if empty"
                    />
                    @error('createCustomCode')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Internal title
                    </label>
                    <input
                        type="text"
                        wire:model="createTitle"
                        class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                        placeholder="e.g. Holiday batch A"
                    />
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Event / campaign label
                    </label>
                    <input
                        type="text"
                        wire:model="createEventLabel"
                        class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                        placeholder="e.g. Summer league 2026"
                    />
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Redeemable from (optional)
                    </label>
                    <input
                        type="datetime-local"
                        wire:model="createValidFrom"
                        class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                    />
                    @error('createValidFrom')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Redeemable until (optional)
                    </label>
                    <input
                        type="datetime-local"
                        wire:model="createValidUntil"
                        class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                    />
                    @error('createValidUntil')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
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
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        Each booking that applies this code counts as one use.
                    </p>
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
                    <textarea
                        wire:model="createNotes"
                        rows="2"
                        class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                    ></textarea>
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
                <option value="">All rows</option>
                <option value="__platform__">Platform only</option>
                @foreach ($clients as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </x-dashboard.table-filter>
            <x-dashboard.table-filter wire:model.live="issuedByFilter" label="Issued by">
                <option value="">Everyone</option>
                <option value="coaches">Coaches</option>
                <option value="staff">Staff &amp; other</option>
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
                <th class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-300">Issued by</th>
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
                <th class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-300">Window</th>
                <th class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-300">Label</th>
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
            <tr wire:key="gc-{{ $card->id }}">
                <td class="px-4 py-3 font-mono text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ $card->code }}
                </td>
                <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                    @if ($card->isPlatformWide())
                        <span
                            class="font-medium text-violet-700 dark:text-violet-300"
                        >
                            All venues
                        </span>
                    @else
                        {{ $card->courtClient?->name ?? '—' }}
                    @endif
                </td>
                <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-400">
                    @if ($card->creator)
                        <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $card->creator->name }}</span>
                        @if (($card->creator->userType->slug ?? '') === \App\Models\UserType::SLUG_COACH)
                            <span
                                class="ml-1 rounded bg-violet-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-violet-800 dark:bg-violet-950/60 dark:text-violet-200"
                            >
                                Coach
                            </span>
                        @endif
                    @else
                        —
                    @endif
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
                    <span
                        class="rounded-full px-2 py-0.5 text-xs font-medium {{ $this->statusBadgeClasses($st) }}"
                    >
                        {{ $st }}
                    </span>
                </td>
                <td class="px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400">
                    @if ($card->valid_from)
                        {{ $card->valid_from->isoFormat('MMM D, g:mma') }}
                    @else
                        —
                    @endif
                    <span class="text-zinc-400">→</span>
                    @if ($card->valid_until)
                        {{ $card->valid_until->isoFormat('MMM D, g:mma') }}
                    @else
                        —
                    @endif
                </td>
                <td class="max-w-[10rem] truncate px-4 py-3 text-xs text-zinc-600 dark:text-zinc-400">
                    @if ($card->title)
                        <span class="font-medium">{{ $card->title }}</span>
                    @endif
                    @if ($card->event_label)
                        <span class="block text-zinc-500">{{ $card->event_label }}</span>
                    @endif
                    @if (! $card->title && ! $card->event_label)
                        —
                    @endif
                </td>
                <td class="px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ $card->created_at?->isoFormat('MMM D, YYYY') }}
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <a
                            href="{{ route('admin.gift-cards.show', $card) }}"
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
                <td colspan="12" class="px-4 py-8 text-center text-zinc-500">No gift cards yet.</td>
            </tr>
        @endforelse
    </x-dashboard.data-table>
</div>
