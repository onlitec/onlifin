<?php

namespace App\Livewire\Settings\Users;

use App\Models\User;
use App\Models\Role;
use Livewire\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class EditUser extends Component
{
    public User $user;
    public $userId;
    public $name;
    public $email;
    public $password;
    public $password_confirmation;
    public $is_admin = false;
    public $selectedRoles = [];

    protected $listeners = ['resetForm'];

    public function mount(User $user)
    {
        $this->resetForm($user);
    }

    public function resetForm($user = null)
    {
        $user = $user ?? $this->user;
        $this->user = $user;
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->is_admin = $user->is_admin;
        $this->selectedRoles = $user->roles->pluck('id')->toArray();
        $this->password = '';
        $this->password_confirmation = '';
    }

    public function cancel()
    {
        $this->resetForm();
        $this->dispatch('close-modal');
    }

    protected function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$this->userId,
            'is_admin' => 'boolean',
            'selectedRoles' => 'array'
        ];

        if (filled($this->password)) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
            $rules['password_confirmation'] = ['required', 'string'];
        }

        return $rules;
    }

    public function updateUser()
    {
        $validated = $this->validate();

        try {
            DB::beginTransaction();

            $userData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'is_admin' => $this->is_admin
            ];

            if (filled($this->password)) {
                $userData['password'] = Hash::make($this->password);
            }

            $this->user->update($userData);
            $this->user->roles()->sync($this->selectedRoles);

            DB::commit();

            session()->flash('success', 'Usuário atualizado com sucesso!');
            $this->dispatch('close-modal');
            $this->dispatch('userUpdated');
            
            return redirect()->route('settings.users');

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Erro ao atualizar usuário: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.settings.users.edit-user', [
            'roles' => Role::all()
        ]);
    }
} 