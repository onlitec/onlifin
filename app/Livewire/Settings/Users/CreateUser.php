<?php

namespace App\Livewire\Settings\Users;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class CreateUser extends Component
{
    public $name = '';
    public $email = '';
    public $phone = '';
    public $password = '';
    public $password_confirmation = '';
    public $role_id = '';
    public $status = true;
    public $roles;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'phone' => 'nullable|string|max:15',
        'password' => 'required|string|min:8|confirmed',
        'role_id' => 'required|exists:roles,id',
        'status' => 'boolean'
    ];

    public function mount()
    {
        $this->roles = Role::all();
    }

    public function save()
    {
        $this->validate();
        
        try {
            // Log para debug
            Log::info('Tentando criar usuário', [
                'name' => $this->name,
                'email' => $this->email,
                'status' => $this->status
            ]);
            
            // Criar usuário de forma mais direta
            $user = new User();
            $user->name = $this->name;
            $user->email = $this->email;
            $user->phone = $this->phone;
            $user->password = Hash::make($this->password);
            $user->is_active = $this->status ? true : false;
            $user->email_verified_at = $this->status ? now() : null;
            $user->save();
            
            // Vincular perfil
            if ($this->role_id) {
                $user->roles()->attach($this->role_id);
            }
            
            Log::info('Usuário criado com sucesso', ['id' => $user->id]);
            
            session()->flash('message', 'Usuário criado com sucesso!');
            return redirect()->route('settings.users');
        } catch (\Exception $e) {
            Log::error('Erro ao criar usuário', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            session()->flash('error', 'Erro ao criar usuário: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.settings.users.create-user');
    }
}