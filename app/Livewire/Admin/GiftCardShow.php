<?php

namespace App\Livewire\Admin;

use App\Models\GiftCard;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Gift card')]
class GiftCardShow extends Component
{
    public GiftCard $giftCard;

    public string $editTitle = '';

    public string $editEventLabel = '';

    public string $editNotes = '';

    public string $editValidFrom = '';

    public string $editValidUntil = '';

    public string $editMaxRedemptionsTotal = '';

    public string $editMaxRedemptionsPerUser = '';

    public function mount(GiftCard $giftCard): void
    {
        $this->giftCard = $giftCard->load(['courtClient', 'creator']);
        $this->syncEditorFromModel();
    }

    public function giftCardsIndexUrl(): string
    {
        return route('admin.gift-cards.index');
    }

    protected function syncEditorFromModel(): void
    {
        $tz = config('app.timezone', 'UTC');
        $this->editTitle = $this->giftCard->title ?? '';
        $this->editEventLabel = $this->giftCard->event_label ?? '';
        $this->editNotes = $this->giftCard->notes ?? '';
        $this->editValidFrom = $this->giftCard->valid_from !== null
            ? $this->giftCard->valid_from->timezone($tz)->format('Y-m-d\TH:i')
            : '';
        $this->editValidUntil = $this->giftCard->valid_until !== null
            ? $this->giftCard->valid_until->timezone($tz)->format('Y-m-d\TH:i')
            : '';
        $this->editMaxRedemptionsTotal = $this->giftCard->max_redemptions_total !== null
            ? (string) $this->giftCard->max_redemptions_total
            : '';
        $this->editMaxRedemptionsPerUser = $this->giftCard->max_redemptions_per_user !== null
            ? (string) $this->giftCard->max_redemptions_per_user
            : '';
    }

    public function saveMetadata(): void
    {
        $this->validate([
            'editTitle' => ['nullable', 'string', 'max:120'],
            'editEventLabel' => ['nullable', 'string', 'max:160'],
            'editNotes' => ['nullable', 'string', 'max:2000'],
            'editValidFrom' => ['nullable', 'date'],
            'editValidUntil' => ['nullable', 'date'],
            'editMaxRedemptionsTotal' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'editMaxRedemptionsPerUser' => ['nullable', 'integer', 'min:1', 'max:999999'],
        ]);

        $tz = config('app.timezone', 'UTC');
        $validFrom = $this->editValidFrom !== '' ? Carbon::parse($this->editValidFrom, $tz) : null;
        $validUntil = $this->editValidUntil !== '' ? Carbon::parse($this->editValidUntil, $tz) : null;

        if ($validFrom !== null && $validUntil !== null && $validUntil->lt($validFrom)) {
            $this->addError('editValidUntil', 'End must be on or after the start.');

            return;
        }

        $maxTotal = $this->editMaxRedemptionsTotal !== '' ? (int) $this->editMaxRedemptionsTotal : null;
        $maxPerUser = $this->editMaxRedemptionsPerUser !== '' ? (int) $this->editMaxRedemptionsPerUser : null;

        if ($maxTotal !== null && $maxTotal < $this->giftCard->bookingsUsingGiftCount()) {
            $this->addError(
                'editMaxRedemptionsTotal',
                'Cannot set max total uses below the number of bookings that already used this code ('.$this->giftCard->bookingsUsingGiftCount().').',
            );

            return;
        }

        if ($maxPerUser !== null) {
            $peak = $this->giftCard->peakBookingsUsingGiftForSingleUser();
            if ($maxPerUser < $peak) {
                $this->addError(
                    'editMaxRedemptionsPerUser',
                    'Cannot set max uses per user below the highest guest usage already recorded ('.$peak.').',
                );

                return;
            }
        }

        if ($maxTotal !== null && $maxPerUser !== null && $maxPerUser > $maxTotal) {
            $this->addError('editMaxRedemptionsPerUser', 'Max uses per user cannot exceed max total uses.');

            return;
        }

        $this->giftCard->update([
            'title' => $this->editTitle !== '' ? $this->editTitle : null,
            'event_label' => $this->editEventLabel !== '' ? $this->editEventLabel : null,
            'notes' => $this->editNotes !== '' ? $this->editNotes : null,
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'max_redemptions_total' => $maxTotal,
            'max_redemptions_per_user' => $maxPerUser,
        ]);

        $this->giftCard->refresh();

        ActivityLogger::log(
            'gift_card.updated',
            ['code' => $this->giftCard->code],
            $this->giftCard,
            "Gift card {$this->giftCard->code} updated",
        );

        session()->flash('status', 'Gift card saved.');
    }

    public function cancelGiftCard(): void
    {
        if ($this->giftCard->cancelled_at !== null) {
            return;
        }

        $this->giftCard->cancelled_at = now();
        $this->giftCard->save();
        $this->giftCard->refresh();

        ActivityLogger::log(
            'gift_card.cancelled',
            ['code' => $this->giftCard->code],
            $this->giftCard,
            "Gift card {$this->giftCard->code} cancelled",
        );

        session()->flash('status', "Gift card {$this->giftCard->code} cancelled.");
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
        $bookings = $this->giftCard->bookings()
            ->with(['courtClient', 'court', 'user'])
            ->orderByDesc('starts_at')
            ->limit(100)
            ->get();

        return view('livewire.admin.gift-card-show', [
            'bookings' => $bookings,
        ]);
    }
}
