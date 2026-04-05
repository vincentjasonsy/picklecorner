<?php

namespace App\Livewire\Admin;

use App\Models\CourtClient;
use App\Models\User;
use App\Models\UserType;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
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

    public function mount(?User $user = null): void
    {
        $this->user = ($user !== null && $user->exists) ? $user : null;

        if ($this->user) {
            $this->name = $user->name;
            $this->email = $user->email;
            $this->user_type_id = (string) $user->user_type_id;
            $this->desk_court_client_id = (string) ($user->desk_court_client_id ?? '');
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
            throw \Illuminate\Validation\ValidationException::withMessages([
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
                throw \Illuminate\Validation\ValidationException::withMessages([
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
            'isEdit' => $this->user !== null,
            'heading' => $this->user ? 'Edit user' : 'Create user',
        ]);
    }
}
