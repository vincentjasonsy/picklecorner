<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::tool-focus')]
#[Title('GameQ (Beta)')]
class OpenPlayOrganizer extends Component
{
    public function render()
    {
        return view('livewire.open-play-organizer');
    }
}
