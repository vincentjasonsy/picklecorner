<?php

namespace App\Livewire;

use App\Models\OpenPlayShare;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::guest')]
#[Title('PickleGameQ · Live (Beta)')]
class OpenPlayWatch extends Component
{
    public OpenPlayShare $openPlayShare;

    public function mount(OpenPlayShare $openPlayShare): void
    {
        $this->openPlayShare = $openPlayShare;
    }

    public function render()
    {
        return view('livewire.open-play-watch', [
            'p' => $this->openPlayShare->fresh()->payload ?? [],
        ]);
    }
}
