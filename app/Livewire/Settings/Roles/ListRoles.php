<?php

namespace App\Livewire\Settings\Roles;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Role;

class ListRoles extends Component
{
    use WithPagination;

    protected $listeners = [
        'roleCreated' => '$refresh',
        'roleUpdated' => '$refresh',
        'roleDeleted' => '$refresh'
    ];

    public function render()
    {
        return view('livewire.settings.roles.list-roles', [
            'roles' => Role::with('permissions')->paginate(10)
        ]);
    }
}