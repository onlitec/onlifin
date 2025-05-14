<?php

namespace App\Livewire\Settings\Users;

use Livewire\Component;
use App\Models\User;
use LivewireUI\Modal\ModalComponent;

class Delete extends ModalComponent
{
    public $userId;
    public $userName;

    public function mount($user)
    {
        $this->userId = $user;
        $this->loadUser();
    }

    public function loadUser()
    {
        $user = User::find($this->userId);
        if ($user) {
            $this->userName = $user->name;
        }
    }

    public function delete()
    {
        $user = User::find($this->userId);
        if ($user) {
            $user->delete();
            $this->closeModalWithEvents([
                'userDeleted' => $this->userId,
            ]);
            session()->flash('message', 'Usuário excluído com sucesso!');
        }
    }

    public function render()
    {
        return view('livewire.settings.users.delete');
    }
}
