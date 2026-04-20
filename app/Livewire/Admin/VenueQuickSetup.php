<?php

namespace App\Livewire\Admin;

use App\Models\CourtClient;
use App\Models\User;
use App\Models\UserType;
use App\Services\ActivityLogger;
use App\Services\CourtClientBootstrap;
use App\Support\PesosMoneyForm;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Quick venue setup')]
class VenueQuickSetup extends Component
{
    public bool $setupComplete = false;

    public ?string $createdVenueId = null;

    public string $createdVenueName = '';

    public string $createdAdminEmail = '';

    public string $createdAdminPasswordPlain = '';

    public string $createdDeskEmail = '';

    public string $createdDeskPasswordPlain = '';

    public bool $createdDeskAccount = false;

    public string $name = '';

    public string $slug = '';

    public string $city = '';

    public string $notes = '';

    public string $venue_status = CourtClient::VENUE_STATUS_ACTIVE;

    public string $hourly_rate_pesos = '';

    public string $peak_hourly_rate_pesos = '';

    public string $currency = 'PHP';

    public string $desk_booking_policy = CourtClient::DESK_BOOKING_POLICY_MANUAL;

    public string $admin_name = '';

    public string $admin_email = '';

    public string $admin_password = '';

    public string $admin_password_confirmation = '';

    public bool $create_desk_account = true;

    public string $desk_name = '';

    public string $desk_email = '';

    public string $desk_password = '';

    public string $desk_password_confirmation = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }

    public function startAnother(): void
    {
        $this->setupComplete = false;
        $this->createdVenueId = null;
        $this->createdVenueName = '';
        $this->createdAdminEmail = '';
        $this->createdAdminPasswordPlain = '';
        $this->createdDeskEmail = '';
        $this->createdDeskPasswordPlain = '';
        $this->createdDeskAccount = false;
        $this->reset([
            'name', 'slug', 'city', 'notes', 'venue_status',
            'hourly_rate_pesos', 'peak_hourly_rate_pesos', 'currency', 'desk_booking_policy',
            'admin_name', 'admin_email', 'admin_password', 'admin_password_confirmation',
            'desk_name', 'desk_email', 'desk_password', 'desk_password_confirmation',
        ]);
        $this->create_desk_account = true;
        $this->venue_status = CourtClient::VENUE_STATUS_ACTIVE;
        $this->currency = 'PHP';
        $this->desk_booking_policy = CourtClient::DESK_BOOKING_POLICY_MANUAL;
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $pesoRegex = '/'.PesosMoneyForm::pesoFieldRegex().'/';
        $courtAdminTypeId = UserType::query()->where('slug', UserType::SLUG_COURT_ADMIN)->value('id');
        $deskTypeId = UserType::query()->where('slug', UserType::SLUG_COURT_CLIENT_DESK)->value('id');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'venue_status' => ['required', 'string', Rule::in(CourtClient::venueStatusValues())],
            'hourly_rate_pesos' => ['nullable', 'string', 'regex:'.$pesoRegex],
            'peak_hourly_rate_pesos' => ['nullable', 'string', 'regex:'.$pesoRegex],
            'currency' => ['required', 'string', 'size:3'],
            'desk_booking_policy' => ['required', 'string', Rule::in(CourtClient::deskBookingPolicyValues())],
            'create_desk_account' => ['boolean'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'confirmed', Password::defaults()],
            'desk_name' => ['required_if:create_desk_account,true', 'nullable', 'string', 'max:255'],
            'desk_email' => [
                'required_if:create_desk_account,true',
                'nullable',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:users,email',
                'different:admin_email',
            ],
            'desk_password' => $this->create_desk_account
                ? ['required', 'string', 'confirmed', Password::defaults()]
                : ['nullable', 'string', 'confirmed'],
        ], [
            'hourly_rate_pesos.regex' => 'Use pesos with up to 2 decimal places (e.g. 350 or 350.50).',
            'peak_hourly_rate_pesos.regex' => 'Use pesos with up to 2 decimal places (e.g. 350 or 350.50).',
            'desk_email.different' => 'Desk email must differ from the court admin email.',
        ]);

        $hourlyCents = PesosMoneyForm::pesoFieldToCents($validated['hourly_rate_pesos']);
        $peakCents = PesosMoneyForm::pesoFieldToCents($validated['peak_hourly_rate_pesos']);

        foreach (['hourly' => $hourlyCents, 'peak' => $peakCents] as $label => $cents) {
            if ($cents !== null && $cents > 100_000_000) {
                $this->addError($label === 'hourly' ? 'hourly_rate_pesos' : 'peak_hourly_rate_pesos', 'Amount is too large.');

                return;
            }
        }

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

        $adminPasswordPlain = $validated['admin_password'];
        $deskPasswordPlain = $validated['create_desk_account'] ? (string) $validated['desk_password'] : '';

        $client = null;
        $deskUser = null;

        DB::transaction(function () use (
            $validated,
            $slug,
            $hourlyCents,
            $peakCents,
            $courtAdminTypeId,
            $deskTypeId,
            $adminPasswordPlain,
            $deskPasswordPlain,
            &$client,
            &$deskUser,
        ): void {
            $admin = User::query()->create([
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'password' => $adminPasswordPlain,
                'user_type_id' => $courtAdminTypeId,
                'desk_court_client_id' => null,
            ]);

            $client = CourtClient::query()->create([
                'name' => $validated['name'],
                'slug' => $slug,
                'city' => $validated['city'],
                'notes' => $validated['notes'],
                'venue_status' => $validated['venue_status'],
                'hourly_rate_cents' => $hourlyCents,
                'peak_hourly_rate_cents' => $peakCents,
                'currency' => $validated['currency'],
                'desk_booking_policy' => $validated['desk_booking_policy'],
                'admin_user_id' => $admin->id,
                'subscription_tier' => CourtClient::TIER_BASIC,
                'public_rating_count' => 0,
            ]);

            CourtClientBootstrap::ensureDefaultCourtsAndSchedule($client);

            ActivityLogger::log(
                'user.created',
                [
                    'email' => $admin->email,
                    'user_type_id' => $admin->user_type_id,
                    'via' => 'admin.venue_quick_setup',
                ],
                $admin,
                "User {$admin->email} created",
            );

            if ($validated['create_desk_account']) {
                $deskUser = User::query()->create([
                    'name' => $validated['desk_name'],
                    'email' => $validated['desk_email'],
                    'password' => $deskPasswordPlain,
                    'user_type_id' => $deskTypeId,
                    'desk_court_client_id' => $client->id,
                ]);

                ActivityLogger::log(
                    'user.created',
                    [
                        'email' => $deskUser->email,
                        'user_type_id' => $deskUser->user_type_id,
                        'desk_court_client_id' => $deskUser->desk_court_client_id,
                        'via' => 'admin.venue_quick_setup',
                    ],
                    $deskUser,
                    "User {$deskUser->email} created",
                );
            }

            ActivityLogger::log(
                'court_client.created',
                ['name' => $client->name, 'slug' => $client->slug, 'via' => 'admin.venue_quick_setup'],
                $client,
                'Court client “'.$client->name.'” created',
            );
        });

        if (! $client instanceof CourtClient) {
            throw new \RuntimeException('Venue quick setup failed to create court client.');
        }

        $this->setupComplete = true;
        $this->createdVenueId = $client->id;
        $this->createdVenueName = $client->name;
        $this->createdAdminEmail = $validated['admin_email'];
        $this->createdAdminPasswordPlain = $adminPasswordPlain;
        $this->createdDeskAccount = (bool) $validated['create_desk_account'];
        if ($deskUser !== null) {
            $this->createdDeskEmail = $deskUser->email;
            $this->createdDeskPasswordPlain = $deskPasswordPlain;
        } else {
            $this->createdDeskEmail = '';
            $this->createdDeskPasswordPlain = '';
        }

        $this->reset([
            'name', 'slug', 'city', 'notes', 'venue_status',
            'hourly_rate_pesos', 'peak_hourly_rate_pesos', 'currency', 'desk_booking_policy',
            'admin_name', 'admin_email', 'admin_password', 'admin_password_confirmation',
            'desk_name', 'desk_email', 'desk_password', 'desk_password_confirmation',
        ]);
        $this->create_desk_account = true;
        $this->venue_status = CourtClient::VENUE_STATUS_ACTIVE;
        $this->currency = 'PHP';
        $this->desk_booking_policy = CourtClient::DESK_BOOKING_POLICY_MANUAL;
    }

    public function render(): View
    {
        return view('livewire.admin.venue-quick-setup');
    }
}
