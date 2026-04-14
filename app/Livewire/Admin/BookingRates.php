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
        $this->recordId = $this->defaultFormRecordId();
        $this->hydrateForm();
    }

    /**
     * Prefer the active row for the editor; otherwise the latest row; otherwise null (new defaults).
     */
    protected function defaultFormRecordId(): ?int
    {
        $active = BookingFeeSetting::query()->where('is_active', true)->first();
        if ($active !== null) {
            return (int) $active->getKey();
        }

        $latest = BookingFeeSetting::query()->latest('id')->first();

        return $latest !== null ? (int) $latest->getKey() : null;
    }

    protected function hydrateForm(): void
    {
        if ($this->recordId !== null) {
            $record = BookingFeeSetting::query()->find($this->recordId);
            if ($record === null) {
                $this->recordId = null;
            } else {
                $this->fillFromModel($record);

                return;
            }
        }

        $defaults = currentBookingFeeSetting();
        $this->base_fee = (string) $defaults->base_fee;
        $this->percentage_fee = (string) $defaults->percentage_fee;
        $this->max_fee = $defaults->max_fee !== null ? (string) $defaults->max_fee : '';
        $this->is_active = true;
    }

    protected function fillFromModel(BookingFeeSetting $record): void
    {
        $this->base_fee = (string) $record->base_fee;
        $this->percentage_fee = (string) $record->percentage_fee;
        $this->max_fee = $record->max_fee !== null ? (string) $record->max_fee : '';
        $this->is_active = $record->is_active;
    }

    /**
     * Start a new rate row (does not save until you submit the form).
     */
    public function startNewRate(): void
    {
        $this->resetValidation();
        $this->recordId = null;
        $this->hydrateForm();
        session()->flash('status', 'Fill in the new rate and save. Check “Active” to apply it at checkout (only one rate can be active).');
    }

    /**
     * Load an existing row into the form for editing.
     */
    public function editRate(int $id): void
    {
        $this->resetValidation();
        $this->recordId = $id;
        $this->hydrateForm();
    }

    /**
     * Return the editor to the currently active rate (or latest).
     */
    public function editDefaultRate(): void
    {
        $this->resetValidation();
        $this->recordId = $this->defaultFormRecordId();
        $this->hydrateForm();
    }

    public function activateRate(int $id): void
    {
        $model = BookingFeeSetting::query()->findOrFail($id);
        $model->update(['is_active' => true]);

        session()->flash('status', "Rate #{$id} is now the active booking fee.");
        if ($this->recordId === $id) {
            $this->hydrateForm();
        }
    }

    public function deactivateRate(int $id): void
    {
        $model = BookingFeeSetting::query()->findOrFail($id);
        $model->update(['is_active' => false]);

        session()->flash('status', "Rate #{$id} is deactivated. Checkout uses defaults until you activate a row.");
        if ($this->recordId === $id) {
            $this->hydrateForm();
        }
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
            $this->recordId = (int) $created->getKey();
        }

        session()->flash('status', 'Booking rate saved.');
        $this->hydrateForm();
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
     * Newest first — historical log.
     *
     * @return Collection<int, BookingFeeSetting>
     */
    #[Computed]
    public function allSettings()
    {
        return BookingFeeSetting::query()->orderByDesc('created_at')->orderByDesc('id')->get();
    }

    #[Computed]
    public function formModeLabel(): string
    {
        if ($this->recordId === null) {
            return 'New rate (unsaved draft)';
        }

        return 'Editing rate #'.$this->recordId;
    }

    public function render(): View
    {
        return view('livewire.admin.booking-rates');
    }
}
