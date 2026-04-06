<?php

namespace App\Livewire\Coach;

use App\Livewire\Admin\GiftCardShow;
use App\Models\GiftCard;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts::member')]
#[Title('Gift card')]
class CoachGiftCardShow extends GiftCardShow
{
    public function mount(GiftCard $giftCard): void
    {
        abort_unless(
            $giftCard->created_by !== null
            && (string) $giftCard->created_by === (string) auth()->id(),
            403,
        );

        parent::mount($giftCard);
    }

    public function giftCardsIndexUrl(): string
    {
        return route('account.coach.gift-cards.index');
    }
}
