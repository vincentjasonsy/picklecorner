<?php

namespace App\Livewire\Admin;

use App\Models\CourtClient;
use App\Models\User;
use App\Models\UserType;
use App\Services\ActivityLogger;
use App\Services\CourtClientBootstrap;
use App\Support\PesosMoneyForm;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('New court client')]
class CourtClientCreate extends Component
{
    public string $name = '';

    public string $slug = '';

    public string $city = '';

    public string $notes = '';

    public bool $is_active = true;

    public string $hourly_rate_pesos = '';

    public string $peak_hourly_rate_pesos = '';

    public string $currency = 'PHP';

    public string $desk_booking_policy = CourtClient::DESK_BOOKING_POLICY_MANUAL;

    public ?string $admin_user_id = null;

    public function save(): void
    {
        $pesoRegex = '/'.PesosMoneyForm::pesoFieldRegex().'/';

        $courtAdminTypeId = UserType::query()->where('slug', UserType::SLUG_COURT_ADMIN)->value('id');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['boolean'],
            'hourly_rate_pesos' => ['nullable', 'string', 'regex:'.$pesoRegex],
            'peak_hourly_rate_pesos' => ['nullable', 'string', 'regex:'.$pesoRegex],
            'currency' => ['required', 'string', 'size:3'],
            'desk_booking_policy' => ['required', 'string', Rule::in(CourtClient::deskBookingPolicyValues())],
            'admin_user_id' => [
                'required',
                'uuid',
                Rule::exists('users', 'id')->where('user_type_id', $courtAdminTypeId),
                Rule::unique('court_clients', 'admin_user_id'),
            ],
        ], [
            'hourly_rate_pesos.regex' => 'Use pesos with up to 2 decimal places (e.g. 350 or 350.50).',
            'peak_hourly_rate_pesos.regex' => 'Use pesos with up to 2 decimal places (e.g. 350 or 350.50).',
        ]);

        $baseSlug = Str::slug($validated['slug'] !== '' && $validated['slug'] !== null
            ? $validated['slug']
            : $validated['name']);
        if ($baseSlug === '') {
            $baseSlug = 'venue';
        }
        $slug = $baseSlug;
        $n = 2;
        while (CourtClient::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$n;
            $n++;
        }

        $hourlyCents = PesosMoneyForm::pesoFieldToCents($validated['hourly_rate_pesos']);
        $peakCents = PesosMoneyForm::pesoFieldToCents($validated['peak_hourly_rate_pesos']);

        foreach (['hourly' => $hourlyCents, 'peak' => $peakCents] as $label => $cents) {
            if ($cents !== null && $cents > 100_000_000) {
                $this->addError($label === 'hourly' ? 'hourly_rate_pesos' : 'peak_hourly_rate_pesos', 'Amount is too large.');

                return;
            }
        }

        $client = CourtClient::query()->create([
            'name' => $validated['name'],
            'slug' => $slug,
            'city' => $validated['city'],
            'notes' => $validated['notes'],
            'is_active' => $validated['is_active'],
            'hourly_rate_cents' => $hourlyCents,
            'peak_hourly_rate_cents' => $peakCents,
            'currency' => $validated['currency'],
            'desk_booking_policy' => $validated['desk_booking_policy'],
            'admin_user_id' => $validated['admin_user_id'],
            'subscription_tier' => CourtClient::TIER_BASIC,
            'public_rating_count' => 0,
        ]);

        CourtClientBootstrap::ensureDefaultCourtsAndSchedule($client);

        ActivityLogger::log(
            'court_client.created',
            ['name' => $client->name, 'slug' => $client->slug],
            $client,
            'Court client “'.$client->name.'” created',
        );

        session()->flash('status', 'Venue created. You can add slot pricing and details below.');

        $this->redirect(route('admin.court-clients.edit', $client), navigate: true);
    }

    public function render(): View
    {
        $courtAdmins = User::query()
            ->with('userType')
            ->whereHas('userType', fn ($q) => $q->where('slug', UserType::SLUG_COURT_ADMIN))
            ->whereDoesntHave('administeredCourtClient')
            ->orderBy('name')
            ->get();

        return view('livewire.admin.court-client-create', [
            'courtAdmins' => $courtAdmins,
        ]);
    }
}
