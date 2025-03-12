<?php

namespace App\Livewire\Settings\Roles;

use LivewireUI\Modal\ModalComponent;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class Edit extends ModalComponent
{
    public $roleId;
    public $name;
    public $description;
    public $selectedPermissions = [];

    protected function rules()
    {
        return [
            'name' => ['required', 'min:3', Rule::unique('roles')->ignore($this->roleId)],
            'description' => 'nullable|string',
            'selectedPermissions' => 'array'
        ];
    }

    public function mount($role)
    {
        $this->roleId = $role;
        $this->loadRole();
    }

    public function loadRole()
    {
        $role = Role::find($this->roleId);
        if ($role) {
            $this->name = $role->name;
            $this->description = $role->description;
            $this->selectedPermissions = $role->permissions->pluck('id')->toArray();
        } else {
            Log::error('Perfil não encontrado', ['roleId' => $this->roleId]);
        }
    }

    public function save()
    {
        $this->validate();

        $role = Role::find($this->roleId);
        if ($role) {
            $role->name = $this->name;
            $role->description = $this->description;
            $role->save();

            $role->permissions()->sync($this->selectedPermissions);

            $this->closeModalWithEvents([
                'roleUpdated'
            ]);

            session()->flash('message', 'Perfil atualizado com sucesso!');
        } else {
            Log::error('Erro ao atualizar perfil', ['roleId' => $this->roleId]);
        }
    }

    public function getPermissionsProperty()
    {
        return Permission::all();
    }

    public function render()
    {
        return view('livewire.settings.roles.edit');
    }
} 