<?php

namespace App\Livewire\Admin;

use App\Models\CourtClient;
use App\Models\User;
use App\Models\UserType;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('User')]
class UserForm extends Component
{
    public ?User $user = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $user_type_id = '';

    public string $desk_court_client_id = '';

    /** When editing a court admin with a venue, maps to {@see CourtClient::$subscription_tier}. */
    public string $venue_subscription_tier = CourtClient::TIER_BASIC;

    public function mount(?User $user = null): void
    {
        $this->user = ($user !== null && $user->exists) ? $user : null;

        if ($this->user) {
            $this->user->load('administeredCourtClient');
            $this->name = $user->name;
            $this->email = $user->email;
            $this->user_type_id = (string) $user->user_type_id;
            $this->desk_court_client_id = (string) ($user->desk_court_client_id ?? '');
            if ($this->user->administeredCourtClient) {
                $this->venue_subscription_tier = $this->user->administeredCourtClient->subscriptionTierNormalized();
            }
        } else {
            $defaultId = UserType::query()->where('slug', UserType::SLUG_USER)->value('id');
            $this->user_type_id = $defaultId ? (string) $defaultId : '';
        }
    }

    public function save(): void
    {
        $passwordRules = $this->user
            ? (filled($this->password)
                ? ['required', 'string', 'confirmed', Password::defaults()]
                : ['nullable', 'string', 'confirmed'])
            : ['required', 'string', 'confirmed', Password::defaults()];

        $deskTypeId = (string) UserType::query()->where('slug', UserType::SLUG_COURT_CLIENT_DESK)->value('id');
        $courtAdminTypeId = (string) UserType::query()->where('slug', UserType::SLUG_COURT_ADMIN)->value('id');

        $giftControls = booking_gift_subscription_controls_visible();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user?->id),
            ],
            'password' => $passwordRules,
            'user_type_id' => ['required', 'uuid', Rule::exists('user_types', 'id')],
            'desk_court_client_id' => [
                Rule::excludeUnless(fn () => (string) $this->user_type_id === $deskTypeId),
                'required',
                'uuid',
                Rule::exists('court_clients', 'id'),
            ],
            'venue_subscription_tier' => array_merge(
                [Rule::excludeUnless(fn () => $giftControls)],
                $giftControls
                    ? [
                        Rule::requiredIf(function () use ($courtAdminTypeId) {
                            if (! $this->user) {
                                return false;
                            }

                            return (string) $this->user_type_id === $courtAdminTypeId
                                && $this->user->administeredCourtClient !== null;
                        }),
                        'nullable',
                        'string',
                        Rule::in(CourtClient::subscriptionTierValues()),
                    ]
                    : [],
            ),
        ]);

        if ($this->user) {
            $this->assertCanChangeRole($validated['user_type_id']);
        }

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'user_type_id' => $validated['user_type_id'],
        ];

        $payload['desk_court_client_id'] = isset($validated['desk_court_client_id'])
            ? $validated['desk_court_client_id']
            : null;

        if ($this->user) {
            if (filled($validated['password'])) {
                $payload['password'] = $validated['password'];
            }

            $before = $this->user->only(['name', 'email', 'user_type_id', 'desk_court_client_id']);

            $this->user->update($payload);
            $this->user->refresh();

            ActivityLogger::log(
                'user.updated',
                [
                    'before' => $before,
                    'after' => $this->user->only(['name', 'email', 'user_type_id', 'desk_court_client_id']),
                ],
                $this->user,
                "User {$this->user->email} updated",
            );

            if ($giftControls) {
                $this->syncCourtAdminVenueSubscriptionTier(
                    $validated,
                    $courtAdminTypeId,
                );
            }

            session()->flash('status', 'User updated.');

            $this->redirect(route('admin.users.index'), navigate: true);

            return;
        }

        $payload['password'] = $validated['password'];

        $newUser = User::query()->create($payload);

        ActivityLogger::log(
            'user.created',
            [
                'email' => $newUser->email,
                'user_type_id' => $newUser->user_type_id,
                'desk_court_client_id' => $newUser->desk_court_client_id,
            ],
            $newUser,
            "User {$newUser->email} created",
        );

        session()->flash('status', 'User created.');

        $this->redirect(route('admin.users.index'), navigate: true);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncCourtAdminVenueSubscriptionTier(array $validated, string $courtAdminTypeId): void
    {
        if ((string) $validated['user_type_id'] !== $courtAdminTypeId) {
            return;
        }

        $this->user?->load('administeredCourtClient');
        $client = $this->user?->administeredCourtClient;
        if ($client === null) {
            return;
        }

        $tier = $validated['venue_subscription_tier'] ?? null;
        if (! is_string($tier) || ! in_array($tier, CourtClient::subscriptionTierValues(), true)) {
            return;
        }

        $before = $client->subscription_tier;
        if ($before === $tier) {
            return;
        }

        $client->update(['subscription_tier' => $tier]);

        ActivityLogger::log(
            'court_client.subscription_tier_updated',
            [
                'before' => $before,
                'after' => $tier,
                'via' => 'admin.users.edit',
                'court_admin_user_id' => $this->user?->id,
            ],
            $client->fresh(),
            'Venue subscription tier updated for “'.$client->name.'”',
        );
    }

    private function assertCanChangeRole(string $newTypeId): void
    {
        if (! $this->user) {
            return;
        }

        $newType = UserType::query()->find($newTypeId);
        if (! $newType) {
            return;
        }

        $wasCourtAdmin = $this->user->isCourtAdmin();
        $stillCourtAdmin = $newType->slug === UserType::SLUG_COURT_ADMIN;

        if ($wasCourtAdmin && ! $stillCourtAdmin && $this->user->administeredCourtClient()->exists()) {
            throw ValidationException::withMessages([
                'user_type_id' => 'This user is assigned as a venue admin. Reassign the venue before changing their role.',
            ]);
        }

        $wasSuperAdmin = $this->user->isSuperAdmin();
        $willBeSuperAdmin = $newType->slug === UserType::SLUG_SUPER_ADMIN;

        if ($wasSuperAdmin && ! $willBeSuperAdmin) {
            $count = User::query()
                ->whereHas('userType', fn ($q) => $q->where('slug', UserType::SLUG_SUPER_ADMIN))
                ->count();

            if ($count <= 1) {
                throw ValidationException::withMessages([
                    'user_type_id' => 'You cannot remove the last super admin account.',
                ]);
            }
        }
    }

    public function render(): View
    {
        return view('livewire.admin.user-form', [
            'typeOptions' => UserType::query()->orderBy('sort_order')->get(),
            'courtClientOptions' => CourtClient::query()->orderBy('name')->get(['id', 'name']),
            'deskUserTypeId' => (string) UserType::query()->where('slug', UserType::SLUG_COURT_CLIENT_DESK)->value('id'),
            'courtAdminTypeId' => (string) UserType::query()->where('slug', UserType::SLUG_COURT_ADMIN)->value('id'),
            'isEdit' => $this->user !== null,
            'heading' => $this->user ? 'Edit user' : 'Create user',
            'giftSubscriptionControlsVisible' => booking_gift_subscription_controls_visible(),
        ]);
    }
}
