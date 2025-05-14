<?php

namespace App\Livewire\Settings\Users;

use Livewire\Component;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use LivewireUI\Modal\ModalComponent;

class Create extends ModalComponent
{
    public $name;
    public $email;
    public $phone;
    public $password;
    public $password_confirmation;
    public $selectedRoles = [];
    public $is_active = true;

    protected $rules = [
        'name' => 'required|min:3',
        'email' => 'required|email|unique:users',
        'phone' => 'nullable|string|max:15',
        'password' => 'required|min:8|confirmed',
        'selectedRoles' => 'array',
        'is_active' => 'boolean'
    ];

    public function mount()
    {
        $this->is_active = true;
    }

    public function save()
    {
        $this->validate();

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'password' => Hash::make($this->password),
            'is_active' => $this->is_active
        ]);

        if (!empty($this->selectedRoles)) {
            $user->roles()->sync($this->selectedRoles);
        }

        $this->closeModalWithEvents([
            'userCreated'
        ]);

        session()->flash('message', 'Usuário criado com sucesso!');
    }

    public function getRolesProperty()
    {
        return Role::all();
    }

    public function render()
    {
        return view('livewire.settings.users.create');
    }
}
