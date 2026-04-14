<?php

namespace App\Livewire\Admin;

use App\Models\BookingFeeSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Booking rates')]
class BookingRates extends Component
{
    public ?int $recordId = null;

    public string $base_fee = '';

    public string $percentage_fee = '';

    public string $max_fee = '';

    public bool $is_active = true;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        $this->loadForm();
    }

    protected function loadForm(): void
    {
        $record = BookingFeeSetting::query()->where('is_active', true)->first()
            ?? BookingFeeSetting::query()->latest('id')->first();

        if ($record === null) {
            $this->recordId = null;
            $this->base_fee = BookingFeeSetting::DEFAULT_BASE_FEE;
            $this->percentage_fee = BookingFeeSetting::DEFAULT_PERCENTAGE_FEE;
            $this->max_fee = BookingFeeSetting::DEFAULT_MAX_FEE;
            $this->is_active = true;

            return;
        }

        $this->recordId = $record->id;
        $this->base_fee = (string) $record->base_fee;
        $this->percentage_fee = (string) $record->percentage_fee;
        $this->max_fee = $record->max_fee !== null ? (string) $record->max_fee : '';
        $this->is_active = $record->is_active;
    }

    public function save(): void
    {
        $rules = [
            'base_fee' => ['required', 'numeric', 'min:0'],
            'percentage_fee' => ['required', 'numeric', 'min:0', 'max:1'],
            'is_active' => ['boolean'],
        ];
        if (trim((string) $this->max_fee) !== '') {
            $rules['max_fee'] = ['numeric', 'min:0'];
        }

        $validated = $this->validate($rules, [], [
            'base_fee' => 'base fee',
            'percentage_fee' => 'percentage fee',
            'max_fee' => 'maximum fee',
        ]);

        $maxFee = trim((string) $this->max_fee) === '' ? null : (float) $this->max_fee;

        $payload = [
            'base_fee' => $validated['base_fee'],
            'percentage_fee' => $validated['percentage_fee'],
            'max_fee' => $maxFee,
            'is_active' => $this->is_active,
        ];

        if ($this->recordId !== null) {
            $model = BookingFeeSetting::query()->findOrFail($this->recordId);
            $model->update($payload);
        } else {
            $created = BookingFeeSetting::query()->create($payload);
            $this->recordId = $created->id;
        }

        session()->flash('status', 'Booking rates saved.');
        $this->loadForm();
    }

    #[Computed]
    public function previewBreakdown(): string
    {
        $m = new BookingFeeSetting([
            'base_fee' => $this->base_fee !== '' ? $this->base_fee : BookingFeeSetting::DEFAULT_BASE_FEE,
            'percentage_fee' => $this->percentage_fee !== '' ? $this->percentage_fee : BookingFeeSetting::DEFAULT_PERCENTAGE_FEE,
            'max_fee' => $this->max_fee !== '' ? $this->max_fee : null,
        ]);

        return $m->breakdownLabel();
    }

    /**
     * @return Collection<int, BookingFeeSetting>
     */
    #[Computed]
    public function allSettings()
    {
        return BookingFeeSetting::query()->orderByDesc('updated_at')->get();
    }

    public function render(): View
    {
        return view('livewire.admin.booking-rates');
    }
}
