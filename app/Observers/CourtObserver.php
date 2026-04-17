<?php

namespace App\Observers;

use App\Models\Court;
use App\Services\CourtOpeningMailNotifier;

class CourtObserver
{
    public function created(Court $court): void
    {
        CourtOpeningMailNotifier::maybeSendForCourt($court);
    }

    public function updated(Court $court): void
    {
        CourtOpeningMailNotifier::maybeSendForCourt($court);
    }
}
