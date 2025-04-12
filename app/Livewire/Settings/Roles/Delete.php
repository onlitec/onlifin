<?php

namespace App\Livewire\Settings\Roles;

use LivewireUI\Modal\ModalComponent;
use App\Models\Role;

class Delete extends ModalComponent
{
    public $roleId;
    public $roleName;

    public function mount($role)
    {
        $this->roleId = $role;
        $this->loadRole();
    }

    public function loadRole()
    {
        $role = Role::find($this->roleId);
        if ($role) {
            $this->roleName = $role->name;
        }
    }

    public function delete()
    {
        $role = Role::find($this->roleId);
        if ($role) {
            $role->delete();
            $this->closeModalWithEvents([
                'roleDeleted'
            ]);
            session()->flash('message', 'Perfil excluído com sucesso!');
        }
    }

    public function render()
    {
        return view('livewire.settings.roles.delete');
    }
} 