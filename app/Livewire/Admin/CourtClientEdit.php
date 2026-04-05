<?php

namespace App\Livewire\Admin;

use App\Models\Court;
use App\Models\CourtClient;
use App\Models\CourtDateSlotBlock;
use App\Models\CourtTimeSlotBlock;
use App\Models\CourtTimeSlotSetting;
use App\Models\User;
use App\Models\UserType;
use App\Models\VenueWeeklyHour;
use App\Services\ActivityLogger;
use App\Services\CourtSlotPricing;
use App\Support\PesosMoneyForm;
use App\Support\VenueScheduleHours;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Edit court client')]
class CourtClientEdit extends Component
{
    /** When true (venue portal), hide super-admin-only fields and skip admin_user_id on save. */
    public bool $isVenuePortal = false;

    public CourtClient $courtClient;

    public string $name = '';

    public string $slug = '';

    public string $city = '';

    public string $notes = '';

    public bool $is_active = true;

    public string $hourly_rate_pesos = '';

    public string $peak_hourly_rate_pesos = '';

    public string $currency = 'PHP';

    /** @see CourtClient::DESK_BOOKING_POLICY_* */
    public string $desk_booking_policy = CourtClient::DESK_BOOKING_POLICY_MANUAL;

    public ?string $admin_user_id = null;

    /** @var list<array{id: ?string, environment: string}> */
    public array $courtRows = [];

    /** @var list<array{id: string, day_of_week: int, is_closed: bool, opens_at: string, closes_at: string}> */
    public array $scheduleRows = [];

    /** @var int 0 = Sunday … 6 = Saturday (matches venue schedule). */
    public int $slotPricingDay = 1;

    /** @var string Y-m-d in app timezone — calendar day for availability grid. */
    public string $availabilityCalendarDate = '';

    public ?string $slotEditCourtId = null;

    public ?int $slotEditHour = null;

    public string $slotEditMode = CourtTimeSlotSetting::MODE_NORMAL;

    public string $slotEditManualPesos = '';

    public ?string $availEditCourtId = null;

    public ?int $availEditHour = null;

    public bool $availEditBlockedOnDate = false;

    public bool $availEditBlockedWeekly = false;

    public function mount(?CourtClient $courtClient = null): void
    {
        abort_unless($courtClient !== null, 404);

        $this->courtClient = $courtClient->load(['courts.timeSlotSettings', 'courts.timeSlotBlocks', 'weeklyHours']);
        $this->syncVenueFromModel();
        $this->ensureDefaultWeeklyHours();
        $this->courtClient->refresh();
        $this->courtClient->load(['courts.timeSlotSettings', 'courts.timeSlotBlocks', 'weeklyHours']);
        $this->syncCourtRowsFromDatabase();
        $this->syncScheduleRowsFromDatabase();
        $this->availabilityCalendarDate = Carbon::now(config('app.timezone', 'UTC'))->format('Y-m-d');
    }

    public function syncVenueFromModel(): void
    {
        $c = $this->courtClient;
        $this->name = $c->name;
        $this->slug = $c->slug;
        $this->city = (string) ($c->city ?? '');
        $this->notes = (string) ($c->notes ?? '');
        $this->is_active = (bool) $c->is_active;
        $this->hourly_rate_pesos = PesosMoneyForm::centsToPesoField($c->hourly_rate_cents);
        $this->peak_hourly_rate_pesos = PesosMoneyForm::centsToPesoField($c->peak_hourly_rate_cents);
        $this->currency = $c->currency ?? 'PHP';
        $this->desk_booking_policy = in_array(
            (string) ($c->desk_booking_policy ?? ''),
            CourtClient::deskBookingPolicyValues(),
            true,
        )
            ? (string) $c->desk_booking_policy
            : CourtClient::DESK_BOOKING_POLICY_MANUAL;
        $this->admin_user_id = $c->admin_user_id;
    }

    protected function ensureDefaultWeeklyHours(): void
    {
        if ($this->courtClient->weeklyHours()->count() > 0) {
            return;
        }

        for ($d = 0; $d < 7; $d++) {
            VenueWeeklyHour::query()->create([
                'court_client_id' => $this->courtClient->id,
                'day_of_week' => $d,
                'is_closed' => false,
                'opens_at' => '07:00',
                'closes_at' => '22:00',
            ]);
        }
    }

    protected function syncCourtRowsFromDatabase(): void
    {
        $courts = $this->courtClient->courts
            ->sortBy([
                fn (Court $c) => $c->environment === Court::ENV_INDOOR ? 1 : 0,
                fn (Court $c) => $c->sort_order,
            ])
            ->values();

        $this->courtRows = $courts->map(fn (Court $c) => [
            'id' => $c->id,
            'environment' => $c->environment,
        ])->all();
    }

    public function courtLabel(int $index): string
    {
        if (! isset($this->courtRows[$index])) {
            return '';
        }

        $env = $this->courtRows[$index]['environment'];
        $ordinal = 1;
        for ($i = 0; $i < $index; $i++) {
            if (($this->courtRows[$i]['environment'] ?? '') === $env) {
                $ordinal++;
            }
        }

        return Court::defaultName($env, $ordinal);
    }

    protected function reorderCourtRowsByEnvironment(): void
    {
        $outdoor = [];
        $indoor = [];
        foreach ($this->courtRows as $row) {
            if (($row['environment'] ?? '') === Court::ENV_INDOOR) {
                $indoor[] = $row;
            } else {
                $outdoor[] = $row;
            }
        }
        $this->courtRows = array_merge($outdoor, $indoor);
    }

    /** @return list<string> */
    protected function courtNamesForRows(): array
    {
        $outdoorN = 0;
        $indoorN = 0;
        $names = [];
        foreach ($this->courtRows as $row) {
            if (($row['environment'] ?? '') === Court::ENV_INDOOR) {
                $indoorN++;
                $names[] = Court::defaultName(Court::ENV_INDOOR, $indoorN);
            } else {
                $outdoorN++;
                $names[] = Court::defaultName(Court::ENV_OUTDOOR, $outdoorN);
            }
        }

        return $names;
    }

    public function updated(string $fullPath, mixed $newValue = null): void
    {
        if (preg_match('/^courtRows\.\d+\.environment$/', $fullPath)) {
            $this->reorderCourtRowsByEnvironment();
        }

        if ($fullPath === 'slotPricingDay') {
            $this->closeSlotEditor();
        }
    }

    public function updatedAvailabilityCalendarDate(): void
    {
        try {
            $this->availabilityCalendarDate = Carbon::parse($this->availabilityCalendarDate, config('app.timezone', 'UTC'))->format('Y-m-d');
        } catch (\Throwable) {
            $this->availabilityCalendarDate = Carbon::now(config('app.timezone', 'UTC'))->format('Y-m-d');
        }
        $this->closeAvailabilityEditor();
    }

    public function shiftAvailabilityDate(int $days): void
    {
        try {
            $d = Carbon::parse($this->availabilityCalendarDate, config('app.timezone', 'UTC'))->addDays($days);
        } catch (\Throwable) {
            $d = Carbon::now(config('app.timezone', 'UTC'))->addDays($days);
        }
        $this->availabilityCalendarDate = $d->format('Y-m-d');
        $this->closeAvailabilityEditor();
    }

    public function availabilityDayOfWeek(): int
    {
        try {
            return (int) Carbon::parse($this->availabilityCalendarDate.' 12:00:00', config('app.timezone', 'UTC'))->format('w');
        } catch (\Throwable) {
            return (int) Carbon::now(config('app.timezone', 'UTC'))->format('w');
        }
    }

    /**
     * @return list<int>
     */
    public function slotHoursForAvailabilityGrid(): array
    {
        return $this->computeSlotHoursForDay($this->availabilityDayOfWeek());
    }

    public function availabilityCalendarDateLabel(): string
    {
        try {
            return Carbon::parse($this->availabilityCalendarDate, config('app.timezone', 'UTC'))->isoFormat('dddd, MMM D, YYYY');
        } catch (\Throwable) {
            return '';
        }
    }

    protected function normalizedAvailabilityCalendarDate(): ?string
    {
        try {
            return Carbon::parse($this->availabilityCalendarDate, config('app.timezone', 'UTC'))->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, true> keys "courtId-hour"
     */
    #[Computed]
    public function availabilityDateBlockLookup(): array
    {
        $courts = $this->courtsOrderedForGrid();
        if ($courts->isEmpty()) {
            return [];
        }

        $date = $this->normalizedAvailabilityCalendarDate();
        if ($date === null) {
            return [];
        }

        $ids = $courts->pluck('id')->all();
        $rows = CourtDateSlotBlock::query()
            ->whereIn('court_id', $ids)
            ->whereDate('blocked_date', $date)
            ->get(['court_id', 'slot_start_hour']);

        $map = [];
        foreach ($rows as $r) {
            $map[$r->court_id.'-'.$r->slot_start_hour] = true;
        }

        return $map;
    }

    /**
     * @return list<int>
     */
    public function slotHoursForGrid(): array
    {
        return $this->computeSlotHoursForDay($this->slotPricingDay);
    }

    /**
     * @return list<int>
     */
    protected function computeSlotHoursForDay(int $dayOfWeek): array
    {
        return VenueScheduleHours::slotStartHoursForDay($this->scheduleRows, $dayOfWeek);
    }

    public function courtsOrderedForGrid(): Collection
    {
        return Court::orderedForGridColumns(
            $this->courtClient->courts()
                ->with(['timeSlotSettings', 'timeSlotBlocks'])
                ->get(),
        );
    }

    public function setSlotPricingDay(int $day): void
    {
        if ($day < 0 || $day > 6) {
            return;
        }

        $this->slotPricingDay = $day;
        $this->closeSlotEditor();
    }

    public function openSlotEditor(string $courtId, int $hour): void
    {
        $this->closeAvailabilityEditor();

        $hours = $this->computeSlotHoursForDay($this->slotPricingDay);
        if (! in_array($hour, $hours, true)) {
            return;
        }

        $court = Court::query()
            ->where('id', $courtId)
            ->where('court_client_id', $this->courtClient->id)
            ->first();

        if (! $court) {
            return;
        }

        $this->slotEditCourtId = $courtId;
        $this->slotEditHour = $hour;

        $existing = CourtTimeSlotSetting::query()
            ->where('court_id', $courtId)
            ->where('day_of_week', $this->slotPricingDay)
            ->where('slot_start_hour', $hour)
            ->first();

        if ($existing) {
            $this->slotEditMode = $existing->mode;
            $this->slotEditManualPesos = PesosMoneyForm::centsToPesoField($existing->amount_cents);
        } else {
            $this->slotEditMode = CourtTimeSlotSetting::MODE_NORMAL;
            $this->slotEditManualPesos = '';
        }

        $this->resetErrorBag(['slotEditMode', 'slotEditManualPesos']);
    }

    public function closeSlotEditor(): void
    {
        $this->slotEditCourtId = null;
        $this->slotEditHour = null;
        $this->slotEditMode = CourtTimeSlotSetting::MODE_NORMAL;
        $this->slotEditManualPesos = '';
        $this->resetErrorBag(['slotEditMode', 'slotEditManualPesos', 'slotEditHour']);
    }

    public function saveSlotPricing(): void
    {
        if ($this->slotEditCourtId === null || $this->slotEditHour === null) {
            return;
        }

        $hours = $this->computeSlotHoursForDay($this->slotPricingDay);
        if (! in_array($this->slotEditHour, $hours, true)) {
            $this->addError('slotEditHour', 'This time slot is outside venue hours for this day.');

            return;
        }

        $this->validate([
            'slotEditMode' => [
                'required',
                Rule::in([
                    CourtTimeSlotSetting::MODE_NORMAL,
                    CourtTimeSlotSetting::MODE_PEAK,
                    CourtTimeSlotSetting::MODE_MANUAL,
                ]),
            ],
            'slotEditManualPesos' => [
                'required_if:slotEditMode,manual',
                'nullable',
                'string',
                'regex:/'.PesosMoneyForm::pesoFieldRegex().'/',
            ],
        ], [
            'slotEditManualPesos.required_if' => 'Enter a peso amount for manual pricing.',
            'slotEditManualPesos.regex' => 'Use pesos with up to 2 decimal places (e.g. 350 or 350.50).',
        ]);

        $manualSlotCents = null;
        if ($this->slotEditMode === CourtTimeSlotSetting::MODE_MANUAL) {
            $manualSlotCents = PesosMoneyForm::pesoFieldToCents($this->slotEditManualPesos);
            if ($manualSlotCents === null || $manualSlotCents < 1) {
                $this->addError('slotEditManualPesos', 'Enter a positive peso amount.');

                return;
            }
            if ($manualSlotCents > 100_000_000) {
                $this->addError('slotEditManualPesos', 'Amount is too large.');

                return;
            }
        }

        $court = Court::query()
            ->where('id', $this->slotEditCourtId)
            ->where('court_client_id', $this->courtClient->id)
            ->firstOrFail();

        if ($this->slotEditMode === CourtTimeSlotSetting::MODE_NORMAL) {
            CourtTimeSlotSetting::query()
                ->where('court_id', $court->id)
                ->where('day_of_week', $this->slotPricingDay)
                ->where('slot_start_hour', $this->slotEditHour)
                ->delete();
        } else {
            CourtTimeSlotSetting::query()->updateOrCreate(
                [
                    'court_id' => $court->id,
                    'day_of_week' => $this->slotPricingDay,
                    'slot_start_hour' => $this->slotEditHour,
                ],
                [
                    'mode' => $this->slotEditMode,
                    'amount_cents' => $this->slotEditMode === CourtTimeSlotSetting::MODE_MANUAL
                        ? $manualSlotCents
                        : null,
                ],
            );
        }

        $this->courtClient->refresh();
        $this->courtClient->load(['courts.timeSlotSettings', 'courts.timeSlotBlocks', 'weeklyHours']);
        $this->closeSlotEditor();

        session()->flash('status', 'Slot pricing updated.');
    }

    public function openAvailabilityEditor(string $courtId, int $hour): void
    {
        $this->closeSlotEditor();

        $hours = $this->slotHoursForAvailabilityGrid();
        if (! in_array($hour, $hours, true)) {
            return;
        }

        $court = Court::query()
            ->where('id', $courtId)
            ->where('court_client_id', $this->courtClient->id)
            ->first();

        if (! $court) {
            return;
        }

        $date = $this->normalizedAvailabilityCalendarDate();
        if ($date === null) {
            return;
        }

        $dow = $this->availabilityDayOfWeek();

        $this->availEditCourtId = $courtId;
        $this->availEditHour = $hour;
        $this->availEditBlockedOnDate = CourtDateSlotBlock::query()
            ->where('court_id', $courtId)
            ->whereDate('blocked_date', $date)
            ->where('slot_start_hour', $hour)
            ->exists();
        $this->availEditBlockedWeekly = CourtTimeSlotBlock::query()
            ->where('court_id', $courtId)
            ->where('day_of_week', $dow)
            ->where('slot_start_hour', $hour)
            ->exists();

        $this->resetErrorBag([
            'availEditBlockedOnDate',
            'availEditBlockedWeekly',
        ]);
    }

    public function closeAvailabilityEditor(): void
    {
        $this->availEditCourtId = null;
        $this->availEditHour = null;
        $this->availEditBlockedOnDate = false;
        $this->availEditBlockedWeekly = false;
        $this->resetErrorBag([
            'availEditBlockedOnDate',
            'availEditBlockedWeekly',
        ]);
    }

    public function saveSlotAvailability(): void
    {
        if ($this->availEditCourtId === null || $this->availEditHour === null) {
            return;
        }

        $hours = $this->slotHoursForAvailabilityGrid();
        if (! in_array($this->availEditHour, $hours, true)) {
            $this->addError('availEditHour', 'This time slot is outside venue hours for this calendar day.');

            return;
        }

        $date = $this->normalizedAvailabilityCalendarDate();
        if ($date === null) {
            $this->addError('availEditHour', 'Invalid calendar date.');

            return;
        }

        $court = Court::query()
            ->where('id', $this->availEditCourtId)
            ->where('court_client_id', $this->courtClient->id)
            ->firstOrFail();

        $dow = $this->availabilityDayOfWeek();

        if ($this->availEditBlockedOnDate) {
            CourtDateSlotBlock::query()->updateOrCreate(
                [
                    'court_id' => $court->id,
                    'blocked_date' => $date,
                    'slot_start_hour' => $this->availEditHour,
                ],
                [],
            );
        } else {
            CourtDateSlotBlock::query()
                ->where('court_id', $court->id)
                ->whereDate('blocked_date', $date)
                ->where('slot_start_hour', $this->availEditHour)
                ->delete();
        }

        if ($this->availEditBlockedWeekly) {
            CourtTimeSlotBlock::query()->updateOrCreate(
                [
                    'court_id' => $court->id,
                    'day_of_week' => $dow,
                    'slot_start_hour' => $this->availEditHour,
                ],
                [],
            );
        } else {
            CourtTimeSlotBlock::query()
                ->where('court_id', $court->id)
                ->where('day_of_week', $dow)
                ->where('slot_start_hour', $this->availEditHour)
                ->delete();
        }

        unset($this->availabilityDateBlockLookup);

        $this->courtClient->refresh();
        $this->courtClient->load(['courts.timeSlotSettings', 'courts.timeSlotBlocks', 'weeklyHours']);

        session()->flash('status', 'Availability saved.');
    }

    protected function nextCalendarDateForWeekday(int $dayOfWeek): string
    {
        $tz = config('app.timezone', 'UTC');
        $cursor = Carbon::now($tz)->startOfDay();

        for ($i = 0; $i < 370; $i++) {
            if ((int) $cursor->format('w') === $dayOfWeek) {
                return $cursor->format('Y-m-d');
            }
            $cursor->addDay();
        }

        return Carbon::now($tz)->format('Y-m-d');
    }

    public function slotHourLabel(int $hour): string
    {
        return Carbon::createFromTime($hour, 0, 0)->format('g:i A');
    }

    protected function syncScheduleRowsFromDatabase(): void
    {
        $this->scheduleRows = $this->courtClient->weeklyHours->map(fn (VenueWeeklyHour $r) => [
            'id' => $r->id,
            'day_of_week' => $r->day_of_week,
            'is_closed' => (bool) $r->is_closed,
            'opens_at' => $r->opens_at ?? '09:00',
            'closes_at' => $r->closes_at ?? '21:00',
        ])->values()->all();
    }

    public function saveVenue(): void
    {
        $pesoRegex = '/'.PesosMoneyForm::pesoFieldRegex().'/';

        $baseRules = [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('court_clients', 'slug')->ignore($this->courtClient->id)],
            'city' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['boolean'],
            'hourly_rate_pesos' => ['nullable', 'string', 'regex:'.$pesoRegex],
            'peak_hourly_rate_pesos' => ['nullable', 'string', 'regex:'.$pesoRegex],
            'currency' => ['required', 'string', 'size:3'],
            'desk_booking_policy' => ['required', 'string', Rule::in(CourtClient::deskBookingPolicyValues())],
        ];

        if ($this->isVenuePortal) {
            $validated = $this->validate($baseRules, [
                'hourly_rate_pesos.regex' => 'Use pesos with up to 2 decimal places (e.g. 350 or 350.50).',
                'peak_hourly_rate_pesos.regex' => 'Use pesos with up to 2 decimal places (e.g. 350 or 350.50).',
            ]);
        } else {
            $courtAdminTypeId = UserType::query()->where('slug', UserType::SLUG_COURT_ADMIN)->value('id');
            $validated = $this->validate(array_merge($baseRules, [
                'admin_user_id' => [
                    'required',
                    'uuid',
                    Rule::exists('users', 'id')->where('user_type_id', $courtAdminTypeId),
                    Rule::unique('court_clients', 'admin_user_id')->ignore($this->courtClient->id),
                ],
            ]), [
                'hourly_rate_pesos.regex' => 'Use pesos with up to 2 decimal places (e.g. 350 or 350.50).',
                'peak_hourly_rate_pesos.regex' => 'Use pesos with up to 2 decimal places (e.g. 350 or 350.50).',
            ]);
        }

        $hourlyCents = PesosMoneyForm::pesoFieldToCents($validated['hourly_rate_pesos']);
        $peakCents = PesosMoneyForm::pesoFieldToCents($validated['peak_hourly_rate_pesos']);

        foreach (['hourly' => $hourlyCents, 'peak' => $peakCents] as $label => $cents) {
            if ($cents !== null && $cents > 100_000_000) {
                $this->addError($label === 'hourly' ? 'hourly_rate_pesos' : 'peak_hourly_rate_pesos', 'Amount is too large.');

                return;
            }
        }

        $payload = [
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'city' => $validated['city'],
            'notes' => $validated['notes'],
            'is_active' => $validated['is_active'],
            'hourly_rate_cents' => $hourlyCents,
            'peak_hourly_rate_cents' => $peakCents,
            'currency' => $validated['currency'],
            'desk_booking_policy' => $validated['desk_booking_policy'],
        ];

        if (! $this->isVenuePortal) {
            $payload['admin_user_id'] = $validated['admin_user_id'];
        }

        $before = $this->courtClient->only(array_keys($payload));

        $this->courtClient->update($payload);
        $this->courtClient->refresh();
        $this->syncVenueFromModel();

        ActivityLogger::log(
            'court_client.updated',
            [
                'before' => $before,
                'after' => $this->courtClient->only(array_keys($payload)),
            ],
            $this->courtClient,
            "Court client “{$this->courtClient->name}” updated",
        );

        session()->flash('status', 'Venue and pricing saved.');
    }

    public function addOutdoorCourt(): void
    {
        $this->addCourtForEnvironment(Court::ENV_OUTDOOR);
    }

    public function addIndoorCourt(): void
    {
        $this->addCourtForEnvironment(Court::ENV_INDOOR);
    }

    protected function addCourtForEnvironment(string $environment): void
    {
        if (! in_array($environment, [Court::ENV_INDOOR, Court::ENV_OUTDOOR], true)) {
            $environment = Court::ENV_OUTDOOR;
        }

        $row = [
            'id' => null,
            'environment' => $environment,
        ];

        if ($environment === Court::ENV_OUTDOOR) {
            $insertAt = 0;
            foreach ($this->courtRows as $i => $r) {
                if (($r['environment'] ?? '') === Court::ENV_OUTDOOR) {
                    $insertAt = $i + 1;
                }
            }
            array_splice($this->courtRows, $insertAt, 0, [$row]);
        } else {
            $this->courtRows[] = $row;
        }
    }

    public function removeCourt(int $index): void
    {
        if (! isset($this->courtRows[$index])) {
            return;
        }

        $row = $this->courtRows[$index];

        if (! empty($row['id'])) {
            $court = Court::query()
                ->where('id', $row['id'])
                ->where('court_client_id', $this->courtClient->id)
                ->first();

            if ($court && $court->bookings()->exists()) {
                session()->flash('warning', 'Cannot remove a court that has bookings. Reassign or cancel them first.');

                return;
            }

            $court?->delete();
        }

        unset($this->courtRows[$index]);
        $this->courtRows = array_values($this->courtRows);
        session()->flash('status', 'Court removed.');
    }

    public function saveCourts(): void
    {
        $this->reorderCourtRowsByEnvironment();

        $this->validate([
            'courtRows' => ['array'],
            'courtRows.*.environment' => ['required', Rule::in([Court::ENV_INDOOR, Court::ENV_OUTDOOR])],
        ]);

        $names = $this->courtNamesForRows();

        foreach ($this->courtRows as $i => $row) {
            $basePayload = [
                'court_client_id' => $this->courtClient->id,
                'name' => $names[$i],
                'environment' => $row['environment'],
                'sort_order' => $i,
            ];

            if (! empty($row['id'])) {
                Court::query()
                    ->where('id', $row['id'])
                    ->where('court_client_id', $this->courtClient->id)
                    ->update($basePayload);
            } else {
                $court = Court::query()->create(array_merge($basePayload, [
                    'hourly_rate_cents' => null,
                    'peak_hourly_rate_cents' => null,
                    'is_available' => true,
                ]));
                $this->courtRows[$i]['id'] = $court->id;
            }
        }

        $this->courtClient->refresh();
        $this->courtClient->load(['courts.timeSlotSettings', 'courts.timeSlotBlocks']);
        $this->syncCourtRowsFromDatabase();

        ActivityLogger::log(
            'court_client.courts_saved',
            ['court_count' => count($this->courtRows)],
            $this->courtClient,
            "Courts updated for “{$this->courtClient->name}”",
        );

        session()->flash('status', 'Courts saved.');
    }

    public function saveSchedule(): void
    {
        foreach ($this->scheduleRows as $i => $row) {
            foreach (['opens_at', 'closes_at'] as $key) {
                $v = $row[$key] ?? null;
                if (is_string($v) && strlen($v) > 5) {
                    $this->scheduleRows[$i][$key] = substr($v, 0, 5);
                }
            }
        }

        $this->validate([
            'scheduleRows' => ['array', 'size:7'],
            'scheduleRows.*.is_closed' => ['boolean'],
            'scheduleRows.*.opens_at' => ['nullable', 'string', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'scheduleRows.*.closes_at' => ['nullable', 'string', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
        ]);

        foreach ($this->scheduleRows as $i => $row) {
            if (empty($row['is_closed'])) {
                if (empty($row['opens_at']) || empty($row['closes_at'])) {
                    $this->addError('scheduleRows.'.$i.'.opens_at', 'Set open and close times, or mark the day closed.');

                    return;
                }
            }
        }

        foreach ($this->scheduleRows as $row) {
            VenueWeeklyHour::query()
                ->where('id', $row['id'])
                ->where('court_client_id', $this->courtClient->id)
                ->update([
                    'is_closed' => (bool) $row['is_closed'],
                    'opens_at' => ! empty($row['is_closed']) ? null : $row['opens_at'],
                    'closes_at' => ! empty($row['is_closed']) ? null : $row['closes_at'],
                ]);
        }

        $this->courtClient->refresh();
        $this->courtClient->load(['weeklyHours', 'courts.timeSlotBlocks']);
        $this->syncScheduleRowsFromDatabase();

        ActivityLogger::log(
            'court_client.schedule_saved',
            [],
            $this->courtClient,
            "Venue schedule updated for “{$this->courtClient->name}”",
        );

        session()->flash('status', 'Venue schedule saved.');
    }

    public function render(): View
    {
        return view('livewire.admin.court-client-edit', [
            'courtAdmins' => User::query()
                ->with('userType')
                ->whereHas('userType', fn ($q) => $q->where('slug', UserType::SLUG_COURT_ADMIN))
                ->orderBy('name')
                ->get(),
            'dayLabels' => VenueWeeklyHour::DAY_LABELS,
        ]);
    }
}
