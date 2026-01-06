<?php

namespace App\Livewire\Settings\Users;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
    public $is_admin = false;
    public $selectedRoles = [];
    public $roles;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'phone' => 'nullable|string|max:15',
        'password' => 'required|string|min:8|confirmed',
        'role_id' => 'nullable|exists:roles,id',
        'status' => 'boolean',
        'is_admin' => 'boolean',
        'selectedRoles' => 'array'
    ];

    public function mount()
    {
        $this->roles = Role::all();
        $this->selectedRoles = [];
    }

    public function closeModal()
    {
        $this->dispatch('close-modal');
    }

    public function save()
    {
        $this->validate();
        
        try {
            DB::beginTransaction();

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
            $user->is_admin = $this->is_admin;
            $user->email_verified_at = $this->status ? now() : null;
            $user->save();
            
            // Vincular perfil
            if ($this->role_id) {
                $user->roles()->attach($this->role_id);
            }

            // Atribui as roles selecionadas
            if (!empty($this->selectedRoles)) {
                $user->roles()->sync($this->selectedRoles);
            }

            DB::commit();
            
            Log::info('Usuário criado com sucesso', ['id' => $user->id]);
            
            // Limpa os campos
            $this->reset();

            // Fecha o modal
            $this->dispatch('close-modal');
            
            session()->flash('message', 'Usuário criado com sucesso!');
            return redirect()->route('settings.users');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar usuário', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            session()->flash('error', 'Erro ao criar usuário: ' . $e->getMessage());
        }
    }

    // Alias para manter compatibilidade
    public function createUser()
    {
        return $this->save();
    }

    public function render()
    {
        $roles = Role::all();
        return view('livewire.settings.users.create-user', [
            'roles' => $roles
        ]);
    }
}
