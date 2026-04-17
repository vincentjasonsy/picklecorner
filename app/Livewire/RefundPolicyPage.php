<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::guest')]
#[Title('Refund policy')]
class RefundPolicyPage extends Component
{
    public function render(): View
    {
        return view('livewire.refund-policy-page');
    }
}
