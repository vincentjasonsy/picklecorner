<?php

namespace App\Livewire\Venue;

use App\Livewire\Admin\GiftCardShow;
use App\Models\GiftCard;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts::venue-portal')]
#[Title('Gift card')]
class VenueGiftCardShow extends GiftCardShow
{
    public function mount(GiftCard $giftCard): void
    {
        $mine = auth()->user()->administeredCourtClient;
        abort_unless(
            $mine !== null
            && $giftCard->court_client_id !== null
            && $giftCard->court_client_id === $mine->id,
            403,
        );

        parent::mount($giftCard);
    }

    public function giftCardsIndexUrl(): string
    {
        return route('venue.gift-cards.index');
    }
}
