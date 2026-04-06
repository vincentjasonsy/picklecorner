<?php

namespace App\Livewire;

use App\Models\OpenPlaySession;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::guest')]
#[Title('PickleGameQ (Beta)')]
class OpenPlayAbout extends Component
{
    public string $historySearch = '';

    public string $historyMonth = '';

    public function render()
    {
        $user = auth()->user();

        $historySessions = collect();
        if ($user) {
            $q = trim($this->historySearch);
            $historySessions = $user->openPlaySessions()
                ->filterHistory($q === '' ? null : $q)
                ->hostedInMonth($this->historyMonth !== '' ? $this->historyMonth : null)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get(['id', 'title', 'created_at', 'updated_at']);
        }

        $monthlyQuota = $user ? OpenPlaySession::quotaForUser($user) : null;

        return view('livewire.open-play-about', [
            'historySessions' => $historySessions,
            'monthlyQuota' => $monthlyQuota,
        ]);
    }
}
