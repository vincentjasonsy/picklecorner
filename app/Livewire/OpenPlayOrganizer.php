<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::member')]
#[Title('PickleGameQ')]
class OpenPlayOrganizer extends Component
{
    public function render()
    {
        return view('livewire.open-play-organizer');
    }
}
