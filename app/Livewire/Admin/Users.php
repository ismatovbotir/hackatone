<?php

namespace App\Livewire\Admin;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::panel')]
class Users extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $role = '';

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    public function activate(int $userId): void
    {
        User::findOrFail($userId)->activate();
        session()->flash('status', t('admin.activated'));
    }

    public function setRole(int $userId, string $role): void
    {
        validator(['role' => $role], ['role' => ['required', Rule::enum(Role::class)]])->validate();

        // Admins cannot change their own role, so there is always at least one admin.
        abort_if($userId === auth()->id(), 403);

        User::findOrFail($userId)->forceFill(['role' => $role])->save();
        session()->flash('status', t('admin.role_saved'));
    }

    public function render()
    {
        $users = User::with('client')
            ->when(in_array($this->role, ['admin', 'user'], true), fn ($q) => $q->where('role', $this->role))
            ->orderBy('role')->latest()
            ->paginate(25);

        return view('livewire.admin.users', ['users' => $users])->title(t('admin.users'));
    }
}
