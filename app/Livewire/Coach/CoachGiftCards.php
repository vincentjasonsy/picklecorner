<?php

namespace App\Livewire\Coach;

use App\Livewire\Concerns\WithDashboardTable;
use App\Models\CourtClient;
use App\Models\GiftCard;
use App\Services\ActivityLogger;
use App\Services\GiftCardService;
use App\Support\PesosMoneyForm;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts::member')]
#[Title('Gift cards')]
class CoachGiftCards extends Component
{
    use WithDashboardTable;

    #[Url]
    public string $q = '';

    #[Url]
    public string $courtFilter = '';

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

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function updatedCourtFilter(): void
    {
        $this->resetPage();
    }

    /** @return list<string> */
    protected function sortableColumns(): array
    {
        return ['created_at', 'balance_cents', 'face_value_cents', 'code'];
    }

    /**
     * Venues this coach has enabled under “Venues you coach”.
     *
     * @return Collection<int, CourtClient>
     */
    #[Computed]
    public function venuesForCoach()
    {
        $ids = auth()->user()->coachedCourtClientIds();
        if ($ids === []) {
            return collect();
        }

        return CourtClient::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city']);
    }

    #[Computed]
    public function giftCardsPaginator()
    {
        $query = GiftCard::query()
            ->with(['courtClient', 'creator'])
            ->where('created_by', auth()->id());

        if ($this->courtFilter !== '') {
            $query->where('court_client_id', $this->courtFilter);
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
        $allowed = auth()->user()->coachedCourtClientIds();
        abort_if($allowed === [], 403);

        $validated = $this->validate([
            'createCourtClientId' => ['required', 'string', 'uuid', Rule::in($allowed)],
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

        $client = CourtClient::query()->findOrFail($validated['createCourtClientId']);

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

        ActivityLogger::log(
            'gift_card.created',
            [
                'code' => $card->code,
                'value_type' => $card->value_type,
                'percent_off' => $card->percent_off,
                'face_value_cents' => $card->face_value_cents,
                'court_client_id' => $card->court_client_id,
                'source' => 'coach',
            ],
            $card,
            "Gift card {$card->code} created by coach for “{$client->name}”",
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
        $this->createCourtClientId = '';
        $this->showCreateForm = false;
        unset($this->giftCardsPaginator);

        session()->flash('status', "Gift card {$card->code} created.");
    }

    public function cancelGiftCard(string $id): void
    {
        $allowed = auth()->user()->coachedCourtClientIds();

        $card = GiftCard::query()
            ->where('id', $id)
            ->where('created_by', auth()->id())
            ->whereIn('court_client_id', $allowed)
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

    public function render(): View
    {
        return view('livewire.coach.coach-gift-cards');
    }
}
