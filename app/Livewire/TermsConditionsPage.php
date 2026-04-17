<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::guest')]
#[Title('Terms & conditions')]
class TermsConditionsPage extends Component
{
    public function render(): View
    {
        return view('livewire.terms-conditions-page');
    }
}
