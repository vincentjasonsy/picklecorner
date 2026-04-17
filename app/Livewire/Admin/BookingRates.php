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
#[Title('Convenience fee')]
class BookingRates extends Component
{
    public ?int $recordId = null;

    public string $fee_basis = BookingFeeSetting::FEE_BASIS_SUBTOTAL;

    public string $per_court_hour_mode = BookingFeeSetting::PER_COURT_HOUR_FIXED;

    public string $per_court_hour_fixed = '';

    public string $per_court_hour_percent = '';

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
        $this->fee_basis = $defaults->fee_basis ?? BookingFeeSetting::FEE_BASIS_SUBTOTAL;
        $this->per_court_hour_mode = $defaults->per_court_hour_mode ?? BookingFeeSetting::PER_COURT_HOUR_FIXED;
        $this->per_court_hour_fixed = $defaults->per_court_hour_fixed !== null
            ? (string) $defaults->per_court_hour_fixed
            : BookingFeeSetting::DEFAULT_PER_COURT_HOUR_FIXED;
        $this->per_court_hour_percent = $defaults->per_court_hour_percent !== null
            ? (string) $defaults->per_court_hour_percent
            : BookingFeeSetting::DEFAULT_PER_COURT_HOUR_PERCENT;
    }

    protected function fillFromModel(BookingFeeSetting $record): void
    {
        $this->base_fee = (string) $record->base_fee;
        $this->percentage_fee = (string) $record->percentage_fee;
        $this->max_fee = $record->max_fee !== null ? (string) $record->max_fee : '';
        $this->is_active = $record->is_active;
        $this->fee_basis = $record->fee_basis ?? BookingFeeSetting::FEE_BASIS_SUBTOTAL;
        $this->per_court_hour_mode = $record->per_court_hour_mode ?? BookingFeeSetting::PER_COURT_HOUR_FIXED;
        $this->per_court_hour_fixed = $record->per_court_hour_fixed !== null ? (string) $record->per_court_hour_fixed : '';
        $this->per_court_hour_percent = $record->per_court_hour_percent !== null ? (string) $record->per_court_hour_percent : '';
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

        session()->flash('status', "Rate #{$id} is now the active convenience fee configuration.");
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
        $basis = $this->fee_basis === BookingFeeSetting::FEE_BASIS_PER_COURT_HOUR
            ? BookingFeeSetting::FEE_BASIS_PER_COURT_HOUR
            : BookingFeeSetting::FEE_BASIS_SUBTOTAL;

        $rules = [
            'fee_basis' => [
                'required',
                'in:'.BookingFeeSetting::FEE_BASIS_SUBTOTAL.','.BookingFeeSetting::FEE_BASIS_PER_COURT_HOUR,
            ],
            'is_active' => ['boolean'],
        ];

        if ($basis === BookingFeeSetting::FEE_BASIS_SUBTOTAL) {
            $rules['base_fee'] = ['required', 'numeric', 'min:0'];
            $rules['percentage_fee'] = ['required', 'numeric', 'min:0', 'max:1'];
            if (trim((string) $this->max_fee) !== '') {
                $rules['max_fee'] = ['numeric', 'min:0'];
            }
        } else {
            $rules['per_court_hour_mode'] = [
                'required',
                'in:'.BookingFeeSetting::PER_COURT_HOUR_FIXED.','.BookingFeeSetting::PER_COURT_HOUR_PERCENT,
            ];
            if ($this->per_court_hour_mode === BookingFeeSetting::PER_COURT_HOUR_FIXED) {
                $rules['per_court_hour_fixed'] = ['required', 'numeric', 'min:0'];
            } else {
                $rules['per_court_hour_percent'] = ['required', 'numeric', 'min:0', 'max:1'];
            }
            if (trim((string) $this->max_fee) !== '') {
                $rules['max_fee'] = ['numeric', 'min:0'];
            }
        }

        $validated = $this->validate($rules, [], [
            'base_fee' => 'base fee',
            'percentage_fee' => 'percentage fee',
            'max_fee' => 'maximum fee',
            'fee_basis' => 'fee basis',
            'per_court_hour_mode' => 'per-court-hour mode',
            'per_court_hour_fixed' => 'fixed fee per court hour',
            'per_court_hour_percent' => 'percentage per court hour',
        ]);

        $maxFee = trim((string) $this->max_fee) === '' ? null : (float) $this->max_fee;
        if ($maxFee !== null && $maxFee <= 0) {
            $maxFee = null;
        }

        if ($basis === BookingFeeSetting::FEE_BASIS_SUBTOTAL) {
            $payload = [
                'base_fee' => $validated['base_fee'],
                'percentage_fee' => $validated['percentage_fee'],
                'max_fee' => $maxFee,
                'is_active' => $this->is_active,
                'fee_basis' => BookingFeeSetting::FEE_BASIS_SUBTOTAL,
                'per_court_hour_mode' => null,
                'per_court_hour_fixed' => null,
                'per_court_hour_percent' => null,
            ];
        } else {
            $payload = [
                'base_fee' => $this->base_fee !== '' ? $this->base_fee : BookingFeeSetting::DEFAULT_BASE_FEE,
                'percentage_fee' => $this->percentage_fee !== '' ? $this->percentage_fee : BookingFeeSetting::DEFAULT_PERCENTAGE_FEE,
                'max_fee' => $maxFee,
                'is_active' => $this->is_active,
                'fee_basis' => BookingFeeSetting::FEE_BASIS_PER_COURT_HOUR,
                'per_court_hour_mode' => $validated['per_court_hour_mode'],
                'per_court_hour_fixed' => $validated['per_court_hour_mode'] === BookingFeeSetting::PER_COURT_HOUR_FIXED
                    ? $validated['per_court_hour_fixed']
                    : null,
                'per_court_hour_percent' => $validated['per_court_hour_mode'] === BookingFeeSetting::PER_COURT_HOUR_PERCENT
                    ? $validated['per_court_hour_percent']
                    : null,
            ];
        }

        if ($this->recordId !== null) {
            $model = BookingFeeSetting::query()->findOrFail($this->recordId);
            $model->update($payload);
        } else {
            $created = BookingFeeSetting::query()->create($payload);
            $this->recordId = (int) $created->getKey();
        }

        session()->flash('status', 'Convenience fee settings saved.');
        $this->hydrateForm();
    }

    #[Computed]
    public function previewBreakdown(): string
    {
        $m = new BookingFeeSetting([
            'base_fee' => $this->base_fee !== '' ? $this->base_fee : BookingFeeSetting::DEFAULT_BASE_FEE,
            'percentage_fee' => $this->percentage_fee !== '' ? $this->percentage_fee : BookingFeeSetting::DEFAULT_PERCENTAGE_FEE,
            'max_fee' => $this->max_fee !== '' ? $this->max_fee : null,
            'fee_basis' => $this->fee_basis !== '' ? $this->fee_basis : BookingFeeSetting::FEE_BASIS_SUBTOTAL,
            'per_court_hour_mode' => $this->per_court_hour_mode !== '' ? $this->per_court_hour_mode : BookingFeeSetting::PER_COURT_HOUR_FIXED,
            'per_court_hour_fixed' => $this->per_court_hour_fixed !== '' ? $this->per_court_hour_fixed : BookingFeeSetting::DEFAULT_PER_COURT_HOUR_FIXED,
            'per_court_hour_percent' => $this->per_court_hour_percent !== '' ? $this->per_court_hour_percent : BookingFeeSetting::DEFAULT_PER_COURT_HOUR_PERCENT,
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
