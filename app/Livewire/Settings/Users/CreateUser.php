<?php

namespace App\Livewire\Settings\Users;

use App\Models\User;
use App\Models\Role;
<<<<<<< HEAD
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
            
=======
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
>>>>>>> remotes/ONLITEC/fix/campo-valor
            session()->flash('error', 'Erro ao criar usuário: ' . $e->getMessage());
        }
    }

    public function render()
    {
<<<<<<< HEAD
        return view('livewire.settings.users.create-user');
    }
}
=======
        $roles = Role::all();
        return view('livewire.settings.users.create-user', [
            'roles' => $roles
        ]);
    }
} 
>>>>>>> remotes/ONLITEC/fix/campo-valor
