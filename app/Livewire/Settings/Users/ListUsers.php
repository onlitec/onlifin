<?php

namespace App\Livewire\Settings\Users;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;

class ListUsers extends Component
{
    use WithPagination;

    protected $listeners = [
        'userCreated' => '$refresh',
        'userUpdated' => '$refresh',
        'userDeleted' => '$refresh'
    ];

    public function render()
    {
        return view('livewire.settings.users.list-users', [
            'users' => User::with('roles')->paginate(10)
        ]);
    }
}