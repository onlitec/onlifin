<?php

namespace App\Livewire\Settings\Users;

use App\Models\User;
use App\Models\Role;
use Livewire\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CreateUser extends Component
{
    public $name;
    public $email;
    public $password;
    public $password_confirmation;
    public $is_admin = false;
    public $selectedRoles = [];
    
    public function mount()
    {
        $this->selectedRoles = [];
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'is_admin' => 'boolean',
            'selectedRoles' => 'array'
        ];
    }

    public function closeModal()
    {
        $this->dispatch('close-modal');
    }

    public function createUser()
    {
        $validated = $this->validate();

        try {
            DB::beginTransaction();

            // Debug da senha antes de hashear
            \Log::info('Senha antes de hashear', [
                'password_length' => strlen($validated['password'])
            ]);

            // Cria o usuário
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']), // Usando bcrypt diretamente
                'is_admin' => $this->is_admin,
                'email_verified_at' => now()
            ]);

            // Debug após criar o usuário
            \Log::info('Usuário criado', [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'password_hash_length' => strlen($user->password)
            ]);

            // Atribui as roles
            if (!empty($this->selectedRoles)) {
                $user->roles()->sync($this->selectedRoles);
            }

            DB::commit();

            // Limpa os campos
            $this->reset();

            // Fecha o modal e redireciona
            $this->dispatch('close-modal');
            session()->flash('success', 'Usuário criado com sucesso!');
            
            return redirect()->route('settings.users');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erro ao criar usuário', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Erro ao criar usuário: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $roles = Role::all();
        return view('livewire.settings.users.create-user', [
            'roles' => $roles
        ]);
    }
} 