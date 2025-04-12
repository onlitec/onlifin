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

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:15',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'status' => 'boolean',
            'is_admin' => 'boolean',
            'selectedRoles' => 'array'
        ];
    }

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
        $validated = $this->validate();
        
        try {
            DB::beginTransaction();
            
            // Log para debug
            Log::info('Tentando criar usuário', [
                'name' => $this->name,
                'email' => $this->email,
                'status' => $this->status
            ]);
            
            // Criar usuário
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $this->phone,
                'password' => Hash::make($validated['password']),
                'is_active' => $this->status ? true : false,
                'is_admin' => $this->is_admin,
                'email_verified_at' => $this->status ? now() : null
            ]);
            
            // Vincular perfil
            if ($this->role_id) {
                $user->roles()->attach($this->role_id);
            }
            
            // Atribui as roles adicionais se existirem
            if (!empty($this->selectedRoles)) {
                $user->roles()->sync($this->selectedRoles);
            }
            
            DB::commit();
            
            Log::info('Usuário criado com sucesso', ['id' => $user->id]);
            
            // Limpa os campos
            $this->reset();
            
            // Fecha o modal e redireciona
            $this->dispatch('close-modal');
            session()->flash('success', 'Usuário criado com sucesso!');
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

    // Alias para manter compatibilidade com chamadas existentes
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
