<?php

namespace App\Livewire\Settings\Users;

use LivewireUI\Modal\ModalComponent;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class Edit extends ModalComponent
{
    public $userId;
    public $name;
    public $email;
    public $phone;
    public $selectedRoles = [];
    public $is_active;

    protected function rules()
    {
        return [
            'name' => 'required|min:3',
            'email' => ['required', 'email', Rule::unique('users')->ignore($this->userId)],
            'phone' => 'nullable|string|max:15',
            'selectedRoles' => 'array',
            'is_active' => 'boolean'
        ];
    }

    public function mount($user)
    {
        $this->userId = $user;
        $this->loadUser();
    }

    public function loadUser()
    {
        $user = User::find($this->userId);
        if ($user) {
            $this->name = $user->name;
            $this->email = $user->email;
            $this->phone = $user->phone;
            $this->is_active = $user->is_active;
            $this->selectedRoles = $user->roles->pluck('id')->toArray();
        } else {
            Log::error('Usuário não encontrado', ['userId' => $this->userId]);
        }
    }

    public function save()
    {
        $this->validate();

        $user = User::find($this->userId);
        if ($user) {
            $user->name = $this->name;
            $user->email = $this->email;
            $user->phone = $this->phone;
            $user->is_active = $this->is_active;
            $user->save();

            $user->roles()->sync($this->selectedRoles);

            $this->closeModalWithEvents([
                'userUpdated'
            ]);

            session()->flash('message', 'Usuário atualizado com sucesso!');
        } else {
            Log::error('Erro ao atualizar usuário', ['userId' => $this->userId]);
        }
    }

    public function getRolesProperty()
    {
        return Role::all();
    }

    public static function modalMaxWidth(): string
    {
        return 'md';
    }

    public function render()
    {
        return view('livewire.settings.users.edit');
    }
}
