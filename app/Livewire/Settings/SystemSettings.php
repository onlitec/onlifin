<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class SystemSettings extends Component
{
    public $current_password = '';
    public $new_password = '';
    public $new_password_confirmation = '';
    public $selectedUserId = null;
    public $userNewPassword = '';
    public $showCurrentPassword = false;
    public $showNewPassword = false;
    public $showUserPassword = false;

    protected $listeners = ['refresh' => '$refresh'];

    protected $rules = [
        'current_password' => 'required',
        'new_password' => 'required|min:8|confirmed',
        'userNewPassword' => 'required|min:8'
    ];

    public function updatePassword()
    {
        $this->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($this->current_password, $user->password)) {
            session()->flash('error', 'A senha atual está incorreta.');
            return;
        }

        $user->update([
            'password' => Hash::make($this->new_password)
        ]);

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        session()->flash('success', 'Senha atualizada com sucesso!');
    }

    public function updateUserPassword()
    {
        // Verifica se o usuário tem permissões de administrador
        if (!Auth::user()->isAdmin()) {
            session()->flash('error', 'Acesso negado. Você não tem permissão para realizar esta ação.');
            return;
        }

        if (!$this->selectedUserId) {
            return;
        }

        $this->validate([
            'userNewPassword' => 'required|min:8'
        ]);

        $user = User::find($this->selectedUserId);
        
        if ($user) {
            $user->update([
                'password' => Hash::make($this->userNewPassword)
            ]);
            
            $this->reset(['selectedUserId', 'userNewPassword']);
            session()->flash('success', 'Senha do usuário atualizada com sucesso!');
        }
    }

    public function selectUser($userId)
    {
        $this->selectedUserId = $userId;
    }

    public function render()
    {
        $users = User::where('id', '!=', Auth::id())->get();
        
        return view('livewire.settings.system-settings', [
            'users' => $users
        ])->layout('layouts.app');
    }
} 