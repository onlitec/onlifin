<?php

namespace App\Livewire\Settings\Roles;

use LivewireUI\Modal\ModalComponent;
use App\Models\Role;
use App\Models\Permission;

class Create extends ModalComponent
{
    public $name;
    public $description;
    public $selectedPermissions = [];

    protected $rules = [
        'name' => 'required|min:3|unique:roles,name',
        'description' => 'nullable|string',
        'selectedPermissions' => 'array'
    ];

    public function save()
    {
        $this->validate();

        $role = Role::create([
            'name' => $this->name,
            'description' => $this->description
        ]);

        if (!empty($this->selectedPermissions)) {
            $role->permissions()->sync($this->selectedPermissions);
        }

        $this->closeModalWithEvents([
            'roleCreated'
        ]);

        session()->flash('message', 'Perfil criado com sucesso!');
    }

    public function getPermissionsProperty()
    {
        return Permission::all();
    }

    public function render()
    {
        return view('livewire.settings.roles.create');
    }
} 